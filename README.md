          ╭──────────────────────╮
          │ Contracts AI Proyect │
          ╰──────────────────────╯
            ▼
           /\_/\
          ( ^.^ )
           > ^ <

Reliable extraction of structured contract data from messy PDFs — without guessing.


## What is this?

This tool processes PDF contracts and extracts structured, validated, and reliable data.

It is designed as a **pre-processing layer** for downstream systems (e.g., OCDS pipelines), ensuring that only trustworthy data moves forward.


## Core Principle

> If data is not reliable → it is discarded (NULL)

No guessing. No silent corrections. No assumptions.

### Why?

Because in contract systems:

- Missing data → can be reviewed  
- Wrong data → corrupts everything downstream  


## What it does

- Extracts text from PDF (native or OCR)
- Detects contract type automatically
- Extracts key fields using deterministic rules
- Normalizes and validates data
- Assigns confidence scores
- Classifies result (accept / review / intervene)


## What it does NOT do

- Does **NOT** invent or infer missing data  
- Does **NOT** complete OCDS structure  
- Does **NOT** replace validation or publishing systems  


## Processing Flow
PDF → Text Extraction → Mapper → Normalization → Evaluation → Decision


### Stages

#### Text Extraction
- Native PDF text OR OCR (Tesseract)

#### Mapping
- Regex + heuristics  
- Contract-type-based extraction  

#### Normalization
- Data typing (dates, numbers)  
- Validation (RFC, formats, logic)  
- Confidence filtering  

#### Evaluation
- Field-level confidence  
- Global score  

#### Decision
- high → accept  
- medium → review  
- low → intervene  


## Output

--- json
{
  "data": { ... },
  "confidence": {
    "global_score": 0.75,
    "fields": { ... },
    "summary": { ... }
  },
  "decision": {
    "classification": "medium",
    "decision": "review"
  }
}


## Example Fields Extracted

- Contract number  
- Supplier name  
- Supplier RFC  
- Dependency  
- Amount  
- Currency  
- Start date / end date  
- Signature date (if detectable)  


## Usage

php artisan test:normalize file.pdf

## Integration

This tool is designed to plug into:

- OCDS generation pipelines  
- Internal validation systems  
- Manual review workflows  

It outputs clean, validated data — not final published structures.


## Tech Stack

- PHP 7.4  
- Laravel 7  
- Tesseract OCR (Spanish)  
- Ghostscript  
- Imagick  


## Known Limitations

- OCR quality directly impacts results  
- Highly distorted PDFs may lose fields  
- Some contract formats may not match existing patterns  


## Scope

This system is intentionally limited in responsibility:

- Ensures data reliability  
- Avoids corruption  
- Signals uncertainty clearly  

Anything beyond that (AI enrichment, OCDS completion, external validation) belongs to downstream systems.


## Design Philosophy

> NULL > Wrong Data  
> Always.


## Author

**Oliver Manríquez Coronado**  
Contracts AI — Internal Tooling · SESEA