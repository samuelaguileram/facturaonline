<?php

declare(strict_types=1);

namespace FacturaOnline\Services\CartaPorte;

use FacturaOnline\Contracts\TransportInterface;
use FacturaOnline\Exceptions\ApiException;

final class CartaPorteService
{
    private const ENDPOINT = 'v4/cfdi40/create';
    private const TYPE_INGRESO = 'carta_porte_ingreso';
    private const TYPE_TRASLADO = 'carta_porte';
    private const ALLOWED_TYPES = [
        self::TYPE_INGRESO,
        self::TYPE_TRASLADO,
    ];
    private const REQUIRED_ROOT_FIELDS = [
        'Receptor',
        'Conceptos',
        'CartaPorte',
    ];
    private const REQUIRED_CARTA_PORTE_FIELDS = [
        'Version',
        'TranspInternac',
        'Ubicaciones',
        'Mercancias',
    ];
    private const REQUIRED_UBICACION_FIELDS = [
        'TipoUbicacion',
        'RFCRemitenteDestinatario',
        'FechaHoraSalidaLlegada',
    ];
    private const REQUIRED_DOMICILIO_FIELDS = [
        'Estado',
        'Pais',
        'CodigoPostal',
    ];
    private const REQUIRED_MERCANCIAS_FIELDS = [
        'PesoBrutoTotal',
        'UnidadPeso',
        'NumTotalMercancias',
        'Mercancia',
    ];
    private const REQUIRED_MERCANCIA_FIELDS = [
        'BienesTransp',
        'Descripcion',
        'Cantidad',
        'ClaveUnidad',
        'PesoEnKg',
    ];
    private const REQUIRED_CANTIDAD_TRANSPORTA_FIELDS = [
        'Cantidad',
        'IDOrigen',
        'IDDestino',
    ];
    private const REQUIRED_AUTOTRANSPORTE_FIELDS = [
        'PermSCT',
        'NumPermisoSCT',
        'IdentificacionVehicular',
        'Seguros',
    ];
    private const REQUIRED_IDENTIFICACION_VEHICULAR_FIELDS = [
        'ConfigVehicular',
        'PesoBrutoVehicular',
        'PlacaVM',
        'AnioModeloVM',
    ];
    private const REQUIRED_SEGUROS_FIELDS = [
        'AseguraRespCivil',
        'PolizaRespCivil',
    ];
    private const REQUIRED_TIPOS_FIGURA_FIELDS = [
        'TipoFigura',
        'NombreFigura',
    ];
    private const REQUIRED_REGIMEN_ADUANERO_FIELDS = [
        'RegimenAduanero',
    ];
    private const REQUIRED_DOCUMENTACION_ADUANERA_FIELDS = [
        'TipoDocumento',
    ];
    private const REQUIRED_GUIA_IDENTIFICACION_FIELDS = [
        'NumeroGuiaIdentificacion',
        'DescripGuiaIdentificacion',
        'PesoGuiaIdentificacion',
    ];
    private const REQUIRED_DETALLE_MERCANCIA_FIELDS = [
        'UnidadPesoMerc',
        'PesoBruto',
        'PesoNeto',
        'PesoTara',
    ];
    private const REQUIRED_TRANSPORTE_AEREO_FIELDS = [
        'PermSCT',
        'NumPermisoSCT',
        'NumeroGuia',
        'CodigoTransportista',
    ];
    private const REQUIRED_TRANSPORTE_FERROVIARIO_FIELDS = [
        'TipoDeServicio',
        'TipoDeTrafico',
        'Carro',
    ];
    private const REQUIRED_DERECHO_DE_PASO_FIELDS = [
        'TipoDerechoDePaso',
        'KilometrajePagado',
    ];
    private const REQUIRED_CARRO_FIELDS = [
        'TipoCarro',
        'MatriculaCarro',
        'GuiaCarro',
        'ToneladasNetasCarro',
    ];
    private const REQUIRED_CONTENEDOR_FERROVIARIO_FIELDS = [
        'TipoContenedor',
        'PesoContenedorVacio',
        'PesoNetoMercancia',
    ];
    private const REQUIRED_TRANSPORTE_MARITIMO_FIELDS = [
        'TipoEmbarcacion',
        'Matricula',
        'NumeroOMI',
        'NacionalidadEmbarc',
        'UnidadesDeArqBruto',
        'TipoCarga',
        'NombreAgenteNaviero',
        'NumAutorizacionNaviero',
    ];
    private const REQUIRED_CONTENEDOR_MARITIMO_FIELDS = [
        'TipoContenedor',
    ];
    private const REQUIRED_REMOLQUE_FIELDS = [
        'SubTipoRem',
        'Placa',
    ];
    private const REQUIRED_REMOLQUE_CCP_FIELDS = [
        'SubtipoRemCCP',
        'PlacaCCP',
    ];

    public function __construct(private readonly TransportInterface $http)
    {
    }

    public function create(array $data): array
    {
        $this->validatePayload($data);

        return $this->http->post(self::ENDPOINT, $data);
    }

    public function crear(array $data): array
    {
        return $this->create($data);
    }

    public function createIngreso(array $data): array
    {
        return $this->createForType($data, self::TYPE_INGRESO);
    }

    public function crearIngreso(array $data): array
    {
        return $this->createIngreso($data);
    }

    public function createTraslado(array $data): array
    {
        return $this->createForType($data, self::TYPE_TRASLADO);
    }

    public function crearTraslado(array $data): array
    {
        return $this->createTraslado($data);
    }

    private function createForType(array $data, string $type): array
    {
        if (array_key_exists('TipoDocumento', $data) && (string) $data['TipoDocumento'] !== $type) {
            throw new ApiException(sprintf('TipoDocumento debe ser "%s" para este método.', $type));
        }

        $data['TipoDocumento'] = $type;

        return $this->create($data);
    }

    private function validatePayload(array $data): void
    {
        foreach (self::REQUIRED_ROOT_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s" es requerido para Carta Porte.', $field));
        }

        $this->assertPresent($data, 'TipoDocumento', 'TipoDocumento es requerido para Carta Porte.');

        if (!in_array((string) $data['TipoDocumento'], self::ALLOWED_TYPES, true)) {
            throw new ApiException('TipoDocumento no es válido para Carta Porte.');
        }

        if (!is_array($data['Receptor'])) {
            throw new ApiException('El campo "Receptor" debe ser un arreglo.');
        }

        $this->assertPresent($data['Receptor'], 'UID', 'El campo "Receptor.UID" es requerido.');

        if (!is_array($data['Conceptos']) || $data['Conceptos'] === []) {
            throw new ApiException('El campo "Conceptos" debe contener al menos un concepto.');
        }

        foreach ($data['Conceptos'] as $index => $concepto) {
            $this->validateConcepto($concepto, (string) $index);
        }

        if (!is_array($data['CartaPorte'])) {
            throw new ApiException('El campo "CartaPorte" debe ser un arreglo.');
        }

        $this->validateCartaPorte($data['CartaPorte']);
    }

    private function validateConcepto(mixed $concepto, string $index): void
    {
        if (!is_array($concepto)) {
            throw new ApiException(sprintf('El nodo "Conceptos.%s" debe ser un arreglo.', $index));
        }

        foreach (['ClaveProdServ', 'Cantidad', 'ClaveUnidad', 'Descripcion'] as $field) {
            $this->assertPresent($concepto, $field, sprintf('El campo "Conceptos.%s.%s" es requerido.', $index, $field));
        }

        $this->assertNumeric($concepto['Cantidad'], sprintf('Conceptos.%s.Cantidad', $index));

        foreach (['ValorUnitario', 'Importe'] as $field) {
            if (array_key_exists($field, $concepto) && trim((string) $concepto[$field]) !== '') {
                $this->assertNumeric($concepto[$field], sprintf('Conceptos.%s.%s', $index, $field));
            }
        }
    }

    private function validateCartaPorte(array $data): void
    {
        foreach (self::REQUIRED_CARTA_PORTE_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.%s" es requerido.', $field));
        }

        if ((string) $data['Version'] !== '3.1') {
            throw new ApiException('El campo "CartaPorte.Version" solo acepta "3.1".');
        }

        if (array_key_exists('IdCCP', $data) && trim((string) $data['IdCCP']) !== '' && !$this->isIdCcp((string) $data['IdCCP'])) {
            throw new ApiException('El campo "CartaPorte.IdCCP" no cumple con el patrón esperado.');
        }

        $transpInternac = $this->normalizeYesNo((string) $data['TranspInternac']);

        if ($transpInternac === null) {
            throw new ApiException('El campo "CartaPorte.TranspInternac" solo acepta valores tipo "Si/Sí" o "No".');
        }

        if ($transpInternac === 'si') {
            $this->assertPresent($data, 'EntradaSalidaMerc', 'El campo "CartaPorte.EntradaSalidaMerc" es requerido cuando TranspInternac es "Sí".');
        }

        if (array_key_exists('TotalDistRec', $data) && trim((string) $data['TotalDistRec']) !== '') {
            $this->assertNumeric($data['TotalDistRec'], 'CartaPorte.TotalDistRec');
        }

        if (array_key_exists('RegistroISTMO', $data) && $this->normalizeYesNo((string) $data['RegistroISTMO']) === 'si') {
            $this->assertPresent($data, 'UbicacionPoloOrigen', 'El campo "CartaPorte.UbicacionPoloOrigen" es requerido cuando RegistroISTMO es "Sí".');
            $this->assertPresent($data, 'UbicacionPoloDestino', 'El campo "CartaPorte.UbicacionPoloDestino" es requerido cuando RegistroISTMO es "Sí".');
        }

        if (array_key_exists('RegimenesAduaneros', $data)) {
            $this->validateRegimenesAduaneros($data['RegimenesAduaneros']);
        }

        $this->validateUbicaciones($data['Ubicaciones']);
        $this->validateMercancias($data['Mercancias']);

        if (array_key_exists('FiguraTransporte', $data)) {
            $this->validateFiguraTransporte($data['FiguraTransporte']);
        }
    }

    private function validateRegimenesAduaneros(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El campo "CartaPorte.RegimenesAduaneros" debe ser un arreglo.');
        }

        $items = $this->extractCollection($data, 'CartaPorte.RegimenesAduaneros', 'RegimenAduaneroCCP');

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.RegimenesAduaneros.RegimenAduaneroCCP.%s" debe ser un arreglo.', $index));
            }

            foreach (self::REQUIRED_REGIMEN_ADUANERO_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.RegimenesAduaneros.RegimenAduaneroCCP.%s.%s" es requerido.', $index, $field)
                );
            }
        }
    }

    private function validateUbicaciones(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El campo "CartaPorte.Ubicaciones" debe ser un arreglo.');
        }

        $ubicaciones = $this->extractCollection($data, 'CartaPorte.Ubicaciones', 'Ubicacion');

        foreach ($ubicaciones as $index => $ubicacion) {
            $this->validateUbicacion($ubicacion, (string) $index);
        }
    }

    private function validateUbicacion(mixed $data, string $index): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "CartaPorte.Ubicaciones.Ubicacion.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_UBICACION_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Ubicaciones.Ubicacion.%s.%s" es requerido.', $index, $field));
        }

        if (!in_array((string) $data['TipoUbicacion'], ['Origen', 'Destino'], true)) {
            throw new ApiException(sprintf('El campo "CartaPorte.Ubicaciones.Ubicacion.%s.TipoUbicacion" debe ser "Origen" o "Destino".', $index));
        }

        $this->assertDateTime(
            (string) $data['FechaHoraSalidaLlegada'],
            sprintf('CartaPorte.Ubicaciones.Ubicacion.%s.FechaHoraSalidaLlegada', $index)
        );

        if (array_key_exists('IDUbicacion', $data) && trim((string) $data['IDUbicacion']) !== '' && !$this->isUbicacionId((string) $data['IDUbicacion'])) {
            throw new ApiException(sprintf('El campo "CartaPorte.Ubicaciones.Ubicacion.%s.IDUbicacion" no cumple el patrón esperado.', $index));
        }

        if (array_key_exists('DistanciaRecorrida', $data) && trim((string) $data['DistanciaRecorrida']) !== '') {
            $this->assertNumeric($data['DistanciaRecorrida'], sprintf('CartaPorte.Ubicaciones.Ubicacion.%s.DistanciaRecorrida', $index));
        }

        if (array_key_exists('Domicilio', $data)) {
            $this->validateDomicilio($data['Domicilio'], sprintf('CartaPorte.Ubicaciones.Ubicacion.%s.Domicilio', $index));
        }
    }

    private function validateDomicilio(mixed $data, string $path): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        foreach (self::REQUIRED_DOMICILIO_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "%s.%s" es requerido.', $path, $field));
        }
    }

    private function validateMercancias(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El campo "CartaPorte.Mercancias" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_MERCANCIAS_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.%s" es requerido.', $field));
        }

        foreach (['PesoBrutoTotal', 'NumTotalMercancias'] as $field) {
            $this->assertNumeric($data[$field], sprintf('CartaPorte.Mercancias.%s', $field));
        }

        if (array_key_exists('PesoNetoTotal', $data) && trim((string) $data['PesoNetoTotal']) !== '') {
            $this->assertNumeric($data['PesoNetoTotal'], 'CartaPorte.Mercancias.PesoNetoTotal');
        }

        $mercancias = $this->extractCollection($data, 'CartaPorte.Mercancias', 'Mercancia');

        if ((int) $data['NumTotalMercancias'] !== count($mercancias)) {
            throw new ApiException('El campo "CartaPorte.Mercancias.NumTotalMercancias" no coincide con el total de nodos "Mercancia".');
        }

        foreach ($mercancias as $index => $mercancia) {
            $this->validateMercancia($mercancia, (string) $index);
        }

        if (array_key_exists('Autotransporte', $data)) {
            $this->validateAutotransporte($data['Autotransporte']);
        }

        if (array_key_exists('TransporteMaritimo', $data)) {
            $this->validateTransporteMaritimo($data['TransporteMaritimo']);
        }

        if (array_key_exists('TransporteAereo', $data)) {
            $this->validateTransporteAereo($data['TransporteAereo']);
        }

        if (array_key_exists('TransporteFerroviario', $data)) {
            $this->validateTransporteFerroviario($data['TransporteFerroviario']);
        }
    }

    private function validateMercancia(mixed $data, string $index): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Mercancia.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_MERCANCIA_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.%s" es requerido.', $index, $field));
        }

        foreach (['Cantidad', 'PesoEnKg'] as $field) {
            $this->assertNumeric($data[$field], sprintf('CartaPorte.Mercancias.Mercancia.%s.%s', $index, $field));
        }

        if (array_key_exists('ValorMercancia', $data) && trim((string) $data['ValorMercancia']) !== '') {
            $this->assertNumeric($data['ValorMercancia'], sprintf('CartaPorte.Mercancias.Mercancia.%s.ValorMercancia', $index));
        }

        if (array_key_exists('FechaCaducidad', $data) && trim((string) $data['FechaCaducidad']) !== '') {
            $this->assertDate($data['FechaCaducidad'], sprintf('CartaPorte.Mercancias.Mercancia.%s.FechaCaducidad', $index));
        }

        if (array_key_exists('UUIDComercioExt', $data) && trim((string) $data['UUIDComercioExt']) !== '' && !$this->isUuid((string) $data['UUIDComercioExt'])) {
            throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.UUIDComercioExt" no es un UUID válido.', $index));
        }

        if (array_key_exists('CantidadTransporta', $data)) {
            $this->validateCantidadTransporta($data['CantidadTransporta'], $index);
        }

        if (array_key_exists('DocumentacionAduanera', $data)) {
            $this->validateDocumentacionAduanera($data['DocumentacionAduanera'], $index);
        }

        if (array_key_exists('GuiasIdentificacion', $data)) {
            $this->validateGuiasIdentificacion($data['GuiasIdentificacion'], $index);
        }

        if (array_key_exists('DetalleMercancia', $data)) {
            $this->validateDetalleMercancia($data['DetalleMercancia'], $index);
        }
    }

    private function validateCantidadTransporta(mixed $data, string $mercanciaIndex): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta" debe contener al menos un registro.', $mercanciaIndex));
        }

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta.%s" debe ser un arreglo.', $mercanciaIndex, $index));
            }

            foreach (self::REQUIRED_CANTIDAD_TRANSPORTA_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta.%s.%s" es requerido.', $mercanciaIndex, $index, $field)
                );
            }

            $this->assertNumeric(
                $item['Cantidad'],
                sprintf('CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta.%s.Cantidad', $mercanciaIndex, $index)
            );

            if (!preg_match('/^OR\d{6}$/', (string) $item['IDOrigen'])) {
                throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta.%s.IDOrigen" no cumple el patrón esperado.', $mercanciaIndex, $index));
            }

            if (!preg_match('/^DE\d{6}$/', (string) $item['IDDestino'])) {
                throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.CantidadTransporta.%s.IDDestino" no cumple el patrón esperado.', $mercanciaIndex, $index));
            }
        }
    }

    private function validateDocumentacionAduanera(mixed $data, string $mercanciaIndex): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.DocumentacionAduanera" debe contener al menos un documento.', $mercanciaIndex));
        }

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Mercancia.%s.DocumentacionAduanera.%s" debe ser un arreglo.', $mercanciaIndex, $index));
            }

            foreach (self::REQUIRED_DOCUMENTACION_ADUANERA_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.DocumentacionAduanera.%s.%s" es requerido.', $mercanciaIndex, $index, $field)
                );
            }
        }
    }

    private function validateGuiasIdentificacion(mixed $data, string $mercanciaIndex): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.GuiasIdentificacion" debe contener al menos una guía.', $mercanciaIndex));
        }

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Mercancia.%s.GuiasIdentificacion.%s" debe ser un arreglo.', $mercanciaIndex, $index));
            }

            foreach (self::REQUIRED_GUIA_IDENTIFICACION_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.GuiasIdentificacion.%s.%s" es requerido.', $mercanciaIndex, $index, $field)
                );
            }

            $this->assertNumeric(
                $item['PesoGuiaIdentificacion'],
                sprintf('CartaPorte.Mercancias.Mercancia.%s.GuiasIdentificacion.%s.PesoGuiaIdentificacion', $mercanciaIndex, $index)
            );
        }
    }

    private function validateDetalleMercancia(mixed $data, string $mercanciaIndex): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.DetalleMercancia" debe contener al menos un detalle.', $mercanciaIndex));
        }

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Mercancia.%s.DetalleMercancia.%s" debe ser un arreglo.', $mercanciaIndex, $index));
            }

            foreach (self::REQUIRED_DETALLE_MERCANCIA_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.Mercancia.%s.DetalleMercancia.%s.%s" es requerido.', $mercanciaIndex, $index, $field)
                );
            }

            foreach (['PesoBruto', 'PesoNeto', 'PesoTara'] as $field) {
                $this->assertNumeric(
                    $item[$field],
                    sprintf('CartaPorte.Mercancias.Mercancia.%s.DetalleMercancia.%s.%s', $mercanciaIndex, $index, $field)
                );
            }

            if (array_key_exists('NumPiezas', $item) && trim((string) $item['NumPiezas']) !== '') {
                $this->assertNumeric(
                    $item['NumPiezas'],
                    sprintf('CartaPorte.Mercancias.Mercancia.%s.DetalleMercancia.%s.NumPiezas', $mercanciaIndex, $index)
                );
            }
        }
    }

    private function validateAutotransporte(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.Autotransporte" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_AUTOTRANSPORTE_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.Autotransporte.%s" es requerido.', $field));
        }

        $this->validateIdentificacionVehicular($data['IdentificacionVehicular']);
        $this->validateSeguros($data['Seguros']);

        if (array_key_exists('Remolques', $data)) {
            $this->validateRemolques($data['Remolques']);
        }
    }

    private function validateIdentificacionVehicular(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.Autotransporte.IdentificacionVehicular" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_IDENTIFICACION_VEHICULAR_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.Autotransporte.IdentificacionVehicular.%s" es requerido.', $field));
        }

        foreach (['PesoBrutoVehicular', 'AnioModeloVM'] as $field) {
            $this->assertNumeric($data[$field], sprintf('CartaPorte.Mercancias.Autotransporte.IdentificacionVehicular.%s', $field));
        }
    }

    private function validateSeguros(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.Autotransporte.Seguros" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_SEGUROS_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.Autotransporte.Seguros.%s" es requerido.', $field));
        }

        $this->assertPair($data, 'AseguraMedAmbiente', 'PolizaMedAmbiente', 'CartaPorte.Mercancias.Autotransporte.Seguros');
        $this->assertPair($data, 'AseguraCarga', 'PolizaCarga', 'CartaPorte.Mercancias.Autotransporte.Seguros');
    }

    private function validateRemolques(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.Autotransporte.Remolques" debe ser un arreglo.');
        }

        $items = $this->extractCollection($data, 'CartaPorte.Mercancias.Autotransporte.Remolques', 'Remolque');

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.Autotransporte.Remolques.Remolque.%s" debe ser un arreglo.', $index));
            }

            foreach (self::REQUIRED_REMOLQUE_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.Autotransporte.Remolques.Remolque.%s.%s" es requerido.', $index, $field)
                );
            }
        }
    }

    private function validateTransporteMaritimo(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.TransporteMaritimo" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_TRANSPORTE_MARITIMO_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.TransporteMaritimo.%s" es requerido.', $field));
        }

        foreach (['UnidadesDeArqBruto', 'AnioEmbarcacion', 'Eslora', 'Manga', 'Calado', 'Puntal'] as $field) {
            if (array_key_exists($field, $data) && trim((string) $data[$field]) !== '') {
                $this->assertNumeric($data[$field], sprintf('CartaPorte.Mercancias.TransporteMaritimo.%s', $field));
            }
        }

        if (array_key_exists('Contenedor', $data)) {
            $this->validateContenedorMaritimo($data['Contenedor']);
        }

        if (array_key_exists('RemolquesCCP', $data)) {
            $this->validateRemolquesCcp($data['RemolquesCCP'], 'CartaPorte.Mercancias.TransporteMaritimo.RemolquesCCP');
        }
    }

    private function validateContenedorMaritimo(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.TransporteMaritimo.Contenedor" debe ser un arreglo.');
        }

        $items = $this->isList($data) ? $data : [$data];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.TransporteMaritimo.Contenedor.%s" debe ser un arreglo.', $index));
            }

            foreach (self::REQUIRED_CONTENEDOR_MARITIMO_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.TransporteMaritimo.Contenedor.%s.%s" es requerido.', $index, $field)
                );
            }

            if (array_key_exists('FechaCertificacionCPP', $item) && trim((string) $item['FechaCertificacionCPP']) !== '') {
                $this->assertDateTime(
                    (string) $item['FechaCertificacionCPP'],
                    sprintf('CartaPorte.Mercancias.TransporteMaritimo.Contenedor.%s.FechaCertificacionCPP', $index)
                );
            }

            if (array_key_exists('IdCCPRelacionado', $item) && trim((string) $item['IdCCPRelacionado']) !== '' && !$this->isIdCcp((string) $item['IdCCPRelacionado'])) {
                throw new ApiException(sprintf('El campo "CartaPorte.Mercancias.TransporteMaritimo.Contenedor.%s.IdCCPRelacionado" no cumple con el patrón esperado.', $index));
            }

            if (array_key_exists('RemolquesCPP', $item)) {
                $this->validateRemolquesCcp($item['RemolquesCPP'], sprintf('CartaPorte.Mercancias.TransporteMaritimo.Contenedor.%s.RemolquesCPP', $index));
            }
        }
    }

    private function validateRemolquesCcp(mixed $data, string $path): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "%s" debe ser un arreglo.', $path));
        }

        $items = $this->extractCollection($data, $path, 'RemolqueCCP');

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "%s.RemolqueCCP.%s" debe ser un arreglo.', $path, $index));
            }

            foreach (self::REQUIRED_REMOLQUE_CCP_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "%s.RemolqueCCP.%s.%s" es requerido.', $path, $index, $field)
                );
            }
        }
    }

    private function validateTransporteAereo(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.TransporteAereo" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_TRANSPORTE_AEREO_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.TransporteAereo.%s" es requerido.', $field));
        }

        if (array_key_exists('MatriculaAeronave', $data) && trim((string) $data['MatriculaAeronave']) !== '' && preg_match('/^([A-Z0-9-]{5,11})$/', (string) $data['MatriculaAeronave']) !== 1) {
            throw new ApiException('El campo "CartaPorte.Mercancias.TransporteAereo.MatriculaAeronave" no cumple el patrón esperado.');
        }
    }

    private function validateTransporteFerroviario(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.Mercancias.TransporteFerroviario" debe ser un arreglo.');
        }

        foreach (self::REQUIRED_TRANSPORTE_FERROVIARIO_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.TransporteFerroviario.%s" es requerido.', $field));
        }

        if (array_key_exists('DerechosDePaso', $data)) {
            $this->validateDerechosDePaso($data['DerechosDePaso']);
        }

        $carros = $this->extractCollection($data, 'CartaPorte.Mercancias.TransporteFerroviario', 'Carro');

        foreach ($carros as $index => $carro) {
            $this->validateCarro($carro, (string) $index);
        }
    }

    private function validateDerechosDePaso(mixed $data): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException('El campo "CartaPorte.Mercancias.TransporteFerroviario.DerechosDePaso" debe contener al menos un registro.');
        }

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.TransporteFerroviario.DerechosDePaso.%s" debe ser un arreglo.', $index));
            }

            foreach (self::REQUIRED_DERECHO_DE_PASO_FIELDS as $field) {
                $this->assertPresent(
                    $item,
                    $field,
                    sprintf('El campo "CartaPorte.Mercancias.TransporteFerroviario.DerechosDePaso.%s.%s" es requerido.', $index, $field)
                );
            }

            $this->assertNumeric(
                $item['KilometrajePagado'],
                sprintf('CartaPorte.Mercancias.TransporteFerroviario.DerechosDePaso.%s.KilometrajePagado', $index)
            );
        }
    }

    private function validateCarro(mixed $data, string $index): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.TransporteFerroviario.Carro.%s" debe ser un arreglo.', $index));
        }

        foreach (self::REQUIRED_CARRO_FIELDS as $field) {
            $this->assertPresent($data, $field, sprintf('El campo "CartaPorte.Mercancias.TransporteFerroviario.Carro.%s.%s" es requerido.', $index, $field));
        }

        $this->assertNumeric(
            $data['ToneladasNetasCarro'],
            sprintf('CartaPorte.Mercancias.TransporteFerroviario.Carro.%s.ToneladasNetasCarro', $index)
        );

        if (array_key_exists('Contenedor', $data)) {
            $this->validateContenedorFerroviario($data['Contenedor'], $index);
        }
    }

    private function validateContenedorFerroviario(mixed $data, string $carroIndex): void
    {
        if (!is_array($data)) {
            throw new ApiException(sprintf('El nodo "CartaPorte.Mercancias.TransporteFerroviario.Carro.%s.Contenedor" debe ser un arreglo.', $carroIndex));
        }

        foreach (self::REQUIRED_CONTENEDOR_FERROVIARIO_FIELDS as $field) {
            $this->assertPresent(
                $data,
                $field,
                sprintf('El campo "CartaPorte.Mercancias.TransporteFerroviario.Carro.%s.Contenedor.%s" es requerido.', $carroIndex, $field)
            );
        }

        foreach (['PesoContenedorVacio', 'PesoNetoMercancia'] as $field) {
            $this->assertNumeric(
                $data[$field],
                sprintf('CartaPorte.Mercancias.TransporteFerroviario.Carro.%s.Contenedor.%s', $carroIndex, $field)
            );
        }
    }

    private function validateFiguraTransporte(mixed $data): void
    {
        if (!is_array($data)) {
            throw new ApiException('El nodo "CartaPorte.FiguraTransporte" debe ser un arreglo.');
        }

        $figuras = $this->extractCollection($data, 'CartaPorte.FiguraTransporte', 'TiposFigura');

        foreach ($figuras as $index => $figura) {
            if (!is_array($figura)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.FiguraTransporte.TiposFigura.%s" debe ser un arreglo.', $index));
            }

            foreach (self::REQUIRED_TIPOS_FIGURA_FIELDS as $field) {
                $this->assertPresent(
                    $figura,
                    $field,
                    sprintf('El campo "CartaPorte.FiguraTransporte.TiposFigura.%s.%s" es requerido.', $index, $field)
                );
            }

            if (array_key_exists('PartesTransporte', $figura)) {
                $this->validatePartesTransporte($figura['PartesTransporte'], $index);
            }

            if (array_key_exists('Domicilio', $figura)) {
                $this->validateDomicilio($figura['Domicilio'], sprintf('CartaPorte.FiguraTransporte.TiposFigura.%s.Domicilio', $index));
            }
        }
    }

    private function validatePartesTransporte(mixed $data, string $figuraIndex): void
    {
        if (!is_array($data) || $data === []) {
            throw new ApiException(sprintf('El campo "CartaPorte.FiguraTransporte.TiposFigura.%s.PartesTransporte" debe contener al menos un registro.', $figuraIndex));
        }

        $items = $this->isList($data) ? $data : [$data];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw new ApiException(sprintf('El nodo "CartaPorte.FiguraTransporte.TiposFigura.%s.PartesTransporte.%s" debe ser un arreglo.', $figuraIndex, $index));
            }

            $this->assertPresent(
                $item,
                'ParteTransporte',
                sprintf('El campo "CartaPorte.FiguraTransporte.TiposFigura.%s.PartesTransporte.%s.ParteTransporte" es requerido.', $figuraIndex, $index)
            );
        }
    }

    private function extractCollection(array $data, string $path, string $childKey): array
    {
        $items = array_key_exists($childKey, $data) ? $data[$childKey] : $data;

        if (!is_array($items) || $items === []) {
            throw new ApiException(sprintf('El campo "%s.%s" debe contener al menos un registro.', $path, $childKey));
        }

        return $items;
    }

    private function assertPair(array $data, string $left, string $right, string $path): void
    {
        $hasLeft = array_key_exists($left, $data) && trim((string) $data[$left]) !== '';
        $hasRight = array_key_exists($right, $data) && trim((string) $data[$right]) !== '';

        if ($hasLeft !== $hasRight) {
            throw new ApiException(sprintf('Los campos "%s.%s" y "%s.%s" deben enviarse juntos.', $path, $left, $path, $right));
        }
    }

    private function assertPresent(array $data, string $field, string $message): void
    {
        if (!array_key_exists($field, $data)) {
            throw new ApiException($message);
        }

        $value = $data[$field];

        if (is_array($value)) {
            if ($value === []) {
                throw new ApiException($message);
            }

            return;
        }

        if (!is_scalar($value) || trim((string) $value) === '') {
            throw new ApiException($message);
        }
    }

    private function assertNumeric(mixed $value, string $field): void
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            throw new ApiException(sprintf('El campo "%s" debe ser numérico.', $field));
        }
    }

    private function assertDateTime(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value);

        if ($date === false || $date->format('Y-m-d\TH:i:s') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d\\TH:i:s".', $field));
        }
    }

    private function assertDate(string $value, string $field): void
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new ApiException(sprintf('El campo "%s" debe tener el formato "Y-m-d".', $field));
        }
    }

    private function normalizeYesNo(string $value): ?string
    {
        $normalized = strtolower(str_replace(['í', 'Í'], 'i', trim($value)));

        return in_array($normalized, ['si', 'no'], true) ? $normalized : null;
    }

    private function isUbicacionId(string $value): bool
    {
        return preg_match('/^(OR|DE)\d{6}$/', $value) === 1;
    }

    private function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $value) === 1;
    }

    private function isIdCcp(string $value): bool
    {
        return preg_match('/^CCC[a-fA-F0-9]{5}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $value) === 1;
    }

    private function isList(array $items): bool
    {
        $expectedKey = 0;

        foreach ($items as $key => $_) {
            if ($key !== $expectedKey) {
                return false;
            }

            $expectedKey++;
        }

        return true;
    }
}
