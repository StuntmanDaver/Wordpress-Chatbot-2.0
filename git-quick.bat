@echo off
:: Gary AI Plugin - Quick Git Batch Commands
:: Simple Windows batch file for common Git operations

setlocal enabledelayedexpansion

:: Color codes for Windows
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"
set "BLUE=[94m"
set "MAGENTA=[95m"
set "RESET=[0m"

echo %MAGENTA%=================================================%RESET%
echo %MAGENTA% Gary AI Plugin - Quick Git Commands%RESET%
echo %MAGENTA%=================================================%RESET%

if "%1"=="" goto :show_help
if "%1"=="help" goto :show_help
if "%1"=="status" goto :git_status
if "%1"=="quick" goto :git_quick
if "%1"=="sync" goto :git_sync
if "%1"=="push" goto :git_push
goto :show_help

:show_help
echo.
echo %BLUE%Available Commands:%RESET%
echo   %GREEN%git-quick.bat status%RESET%  - Check Git repository status
echo   %GREEN%git-quick.bat quick%RESET%   - Quick commit and push (prompts for message)
echo   %GREEN%git-quick.bat sync%RESET%    - Pull latest changes from GitHub
echo   %GREEN%git-quick.bat push%RESET%    - Push committed changes to GitHub
echo   %GREEN%git-quick.bat help%RESET%    - Show this help
echo.
echo %YELLOW%Examples:%RESET%
echo   git-quick.bat quick
echo   git-quick.bat status
echo   git-quick.bat sync
echo.
goto :end

:git_status
echo %BLUE%Checking Git status...%RESET%
git status
echo.
git log -1 --oneline
echo %GREEN%Status check complete!%RESET%
goto :end

:git_quick
echo %BLUE%Quick Git Deploy%RESET%
echo.

:: Check for changes
git status --porcelain >nul 2>&1
if errorlevel 1 (
    echo %RED%Error: Not in a Git repository!%RESET%
    goto :end
)

for /f %%i in ('git status --porcelain') do set HAS_CHANGES=1
if not defined HAS_CHANGES (
    echo %YELLOW%No changes to commit%RESET%
    goto :end
)

echo %YELLOW%Current changes:%RESET%
git status --short

echo.
set /p MESSAGE="Enter commit message: "
if "%MESSAGE%"=="" (
    echo %RED%Commit message cannot be empty!%RESET%
    goto :end
)

echo.
echo %BLUE%Staging all changes...%RESET%
git add -A
if errorlevel 1 (
    echo %RED%Failed to stage changes!%RESET%
    goto :end
)

echo %BLUE%Committing changes...%RESET%
git commit -m "feature: %MESSAGE%"
if errorlevel 1 (
    echo %RED%Failed to commit changes!%RESET%
    goto :end
)

echo %BLUE%Pushing to GitHub...%RESET%
git push
if errorlevel 1 (
    echo %RED%Failed to push to GitHub!%RESET%
    echo %YELLOW%Try running: git pull origin main%RESET%
    goto :end
)

echo %GREEN%Successfully deployed to GitHub!%RESET%
goto :end

:git_sync
echo %BLUE%Syncing with GitHub...%RESET%

git fetch origin
if errorlevel 1 (
    echo %RED%Failed to fetch from GitHub!%RESET%
    goto :end
)

echo %BLUE%Pulling latest changes...%RESET%
git pull
if errorlevel 1 (
    echo %YELLOW%Pull failed - you may have local changes%RESET%
    echo %YELLOW%Check status and resolve conflicts%RESET%
    goto :end
)

echo %GREEN%Successfully synced with GitHub!%RESET%
goto :end

:git_push
echo %BLUE%Pushing to GitHub...%RESET%

git push
if errorlevel 1 (
    echo %RED%Failed to push to GitHub!%RESET%
    echo %YELLOW%Try: git pull origin main%RESET%
    goto :end
)

echo %GREEN%Successfully pushed to GitHub!%RESET%
goto :end

:end
echo.
pause 