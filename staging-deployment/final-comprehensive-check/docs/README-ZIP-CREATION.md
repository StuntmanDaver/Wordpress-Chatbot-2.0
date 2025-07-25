# ⚠️ ZIP File Creation Policy

## Important Notice

**DO NOT CREATE ZIP FILES UNTIL EXPLICITLY INSTRUCTED BY USER**

This repository has been configured to prevent automatic ZIP file creation per user preference. All build scripts have been modified to skip ZIP generation.

## Modified Scripts

### 1. `build-release.js`
- ZIP creation code is commented out
- Displays warning messages
- Only creates release files in `dist/` directory

### 2. `scripts/build-plugin.ps1` 
- Added warnings about ZIP creation policy
- Only performs build operations

### 3. `build/package.json`
- `npm run package` now shows warning instead of creating ZIP
- `npm run release` shows warning message
- New command `npm run package-when-requested` for when user explicitly requests ZIP

## How to Create ZIP When Requested

**ONLY** when the user explicitly requests a ZIP file:

1. **Method 1**: Uncomment the ZIP creation code in `build-release.js` (lines marked with `/⋆ ... ⋆/`)
2. **Method 2**: Run the manual ZIP creation command:
   ```bash
   # From gary-ai directory
   node build-release.js  # This will create release files only
   # Then manually create ZIP if requested:
   powershell -Command "Compress-Archive -Path 'dist\gary-ai\*' -DestinationPath 'gary-ai-1.0.0.zip' -Force"
   ```

## Current Status

- ✅ All build scripts modified to skip ZIP creation
- ✅ Warning messages added throughout codebase  
- ✅ Release files can still be generated in `dist/` directory
- ⚠️ ZIP creation requires explicit user instruction

## User Preference Memory

User has specifically requested: **"Do not produce a zip file until instructed"**

This policy should be maintained until the user explicitly requests ZIP file creation. 