# NormalizerIA – Overview

Este proyecto procesa PDFs de contratos y extrae información clave, normalizándola en un formato uniforme.  

## Commands

- **TestExtractor** – Prueba la extracción de texto del PDF usando OCR si es necesario.
- **TestOCR** – Ejecuta específicamente el OCR sobre PDFs que no contienen texto nativo.
- **TestNormalize** – Ejecuta toda la pipeline de extracción → mapeo → normalización y muestra el resultado final.

## Services

- **PdfTextExtractor** – Servicio que extrae texto de PDFs y aplica OCR si no hay texto disponible.
- **ContractNormalizer** – Servicio que toma datos extraídos y los normaliza en un formato uniforme (fechas, montos, nombres de campos, etc.).

## Services/Mappers

- **ContractMapper** – Mapper específico para contratos SESEA, con reglas de extracción precisas.
- **GenericContractMapper** – Mapper fallback para otros tipos de contrato.
- **MapperInterface** – Interface que define el contrato que todos los mappers deben cumplir (`map(string $text): array`).
- **MapperDetector** – Determina qué mapper usar según el contenido del texto (reglas de detección).
- **MapperFactory** – Crea la instancia correcta de mapper usando `MapperDetector`.


## Fase 6 — Estado de Estabilización

### Comportamiento del sistema

El sistema prioriza la **precisión sobre la completitud**.

- Solo se devuelven campos cuando pueden extraerse con certeza
- Patrones desconocidos o no soportados regresan `null`
- No se permiten valores inferidos ni suposiciones

---

### Casos soportados

Actualmente soporta contratos estándar de SESEA con:

- Tipos de contrato:
  - ADQUISICION_BIENES
  - ADQUISICION_BIENES_BIS
  - SERVICIOS

- Estructura reconocible:
  - Número de contrato (SESEA/...)
  - Declaración del proveedor
  - Cláusula de monto
  - Expresiones de fecha comunes

---

### Casos no soportados (por diseño)

- Tipos de contrato mixtos (ej. SERVICIOS Y SUMINISTRO)
- Redacción legal no estándar
- Fechas complejas o ambiguas
- Documentos sin estructura clara

En estos casos se devolverán datos parciales o `null`.

---

### Reglas de validación

Se aplican después de la extracción:

- fecha_fin ≥ fecha_inicio
- monto debe ser numérico y > 0
- rfc_proveedor debe cumplir formato válido

Valores inválidos se convierten en `null`.

---

### Salida determinista

Misma entrada → misma salida

---

### Uso seguro

El sistema es seguro cuando:

- Se aceptan datos parciales
- La precisión es más importante que la completitud
- Los sistemas consumidores toleran `null`

---

### Siguiente fase

Posibles mejoras:

- Ampliar tipos de contrato soportados de forma controlada
- Integración de IA para casos no cubiertos