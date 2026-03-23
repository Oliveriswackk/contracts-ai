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
