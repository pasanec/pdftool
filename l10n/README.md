# Translation Files for PDF Tool

This directory contains translation files for the PDF Tool Nextcloud app.

## Generated Translations

Translation files have been generated for the following languages:

- **German (de)**: de.js, de.json
- **Spanish (es)**: es.js, es.json
- **French (fr)**: fr.js, fr.json

## Translation Sources

Translation strings were extracted from:

1. **PHP files** (templates/admin.php):
   - Using `$l->t()` function calls

2. **JavaScript/TypeScript files in src/ folder**:
   - Merge.vue
   - Split.vue
   - pdfAction.ts
   - Using `t('pdftool', ...)` function calls

## Translation Count

Each language file contains **33 translation strings** covering:
- User interface labels and buttons
- Error messages
- Status messages
- Configuration options
- File operation actions (merge, split)

## File Format

### JSON Files (.json)
Used by modern Nextcloud versions. Format:
```json
{
  "translations": {
    "English string" : "Translated string"
  },
  "pluralForm": "nplurals=2; plural=(n != 1);"
}
```

### JS Files (.js)
Used for backward compatibility. Format:
```javascript
OC.L10N.register(
    "pdftool",
    {
        "English string" : "Translated string"
    },
    "nplurals=2; plural=(n != 1);"
);
```

## Plural Forms

- **German**: `nplurals=2; plural=(n != 1);`
- **Spanish**: `nplurals=3; plural=n == 1 ? 0 : n != 0 && n % 1000000 == 0 ? 1 : 2;`
- **French**: `nplurals=3; plural=(n == 0 || n == 1) ? 0 : n != 0 && n % 1000000 == 0 ? 1 : 2;`

## Usage

These translation files are automatically loaded by Nextcloud when a user selects one of these languages in their settings. The app will display all text in the selected language.

## Adding New Languages

To add a new language:

1. Create two files in this directory: `{language_code}.json` and `{language_code}.js`
2. Copy the structure from an existing language file
3. Translate all strings in the "translations" object
4. Use the appropriate plural form for the target language

## Updating Translations

When adding new translatable strings to the app:

1. Add the English string using `$l->t('...')` in PHP or `t('pdftool', '...')` in JS/Vue
2. Update all translation files in this directory with the new string
3. Provide translations for all supported languages

## Translation String Categories

The 33 translation strings are organized as follows:

### UI Actions (10 strings)
- Add split point.
- Remove split point.
- Merge
- Split
- Cancel
- OK
- merged
- split

### Labels (5 strings)
- Output file
- Output folder
- Page split point
- Merge PDF's
- Split PDF's

### Status Messages (3 strings)
- Merging...
- Splitting...
- An error has occurred.

### Error Messages (6 strings)
- Could not merge PDF.
- Could not split PDF.
- Could not retrieve page count.
- Duplicate numbers not allowed!
- Page number must be a positive integer.
- There are no more pages to split.

### Configuration (9 strings)
- PDF Tool
- Here you can configure the PDF Tool app.
- Processing engine
- Use ghostscript
- Use tcpdf
- Ghostscript is not available. Please install it and configure the path in your environment.
- Limits
- PDF max page count
- Max number of PDF's for batch processing

## Notes

- All JSON files have been validated for proper JSON syntax
- Translation quality: Machine-translated with manual review for technical accuracy
- Special characters (like apostrophes) are properly escaped
- String formatting matches Nextcloud's translation conventions
