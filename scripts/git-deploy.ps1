#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Gary AI Plugin - Git Deployment Automation Script
    
.DESCRIPTION
    Automates common Git operations for the Gary AI WordPress plugin:
    - Smart commit with automated staging
    - Push to GitHub with branch detection
    - Interactive commit message prompts
    - Safety checks and status reports
    - Support for different deployment types
    
.PARAMETER Action
    The Git action to perform: commit, push, deploy, status, sync
    
.PARAMETER Message
    Custom commit message (optional - will prompt if not provided)
    
.PARAMETER Type
    Type of commit: feature, fix, docs, refactor, test, style, chore
    
.PARAMETER Force
    Skip confirmation prompts (use with caution)
    
.EXAMPLE
    .\scripts\git-deploy.ps1 -Action deploy -Type feature -Message "Add new chat widget"
    .\scripts\git-deploy.ps1 -Action status
    .\scripts\git-deploy.ps1 -Action sync
#>

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("commit", "push", "deploy", "status", "sync", "quick")]
    [string]$Action,
    
    [string]$Message = "",
    
    [ValidateSet("feature", "fix", "docs", "refactor", "test", "style", "chore", "release")]
    [string]$Type = "feature",
    
    [switch]$Force
)

# Colors for output
$Colors = @{
    Success = "Green"
    Warning = "Yellow" 
    Error = "Red"
    Info = "Cyan"
    Header = "Magenta"
}

function Write-ColorOutput {
    param([string]$Text, [string]$Color = "White")
    Write-Host $Text -ForegroundColor $Colors[$Color]
}

function Write-Header {
    param([string]$Title)
    Write-Host "`n" + "="*60 -ForegroundColor $Colors.Header
    Write-Host " $Title" -ForegroundColor $Colors.Header  
    Write-Host "="*60 -ForegroundColor $Colors.Header
}

function Get-GitStatus {
    Write-Header "Git Repository Status"
    
    # Check if we're in a Git repository
    if (-not (Test-Path ".git")) {
        Write-ColorOutput "❌ Not in a Git repository!" "Error"
        return $false
    }
    
    # Get current branch
    $currentBranch = git rev-parse --abbrev-ref HEAD 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput "❌ Failed to get current branch" "Error"
        return $false
    }
    
    Write-ColorOutput "🌿 Current Branch: $currentBranch" "Info"
    
    # Get status
    $status = git status --porcelain 2>$null
    if ($status) {
        Write-ColorOutput "📝 Changes detected:" "Warning"
        git status --short
    } else {
        Write-ColorOutput "✅ Working directory clean" "Success"
    }
    
    # Get last commit
    $lastCommit = git log -1 --oneline 2>$null
    if ($lastCommit) {
        Write-ColorOutput "📎 Last Commit: $lastCommit" "Info"
    }
    
    # Check remote status
    git fetch origin 2>$null
    $behind = git rev-list --count HEAD..origin/$currentBranch 2>$null
    $ahead = git rev-list --count origin/$currentBranch..HEAD 2>$null
    
    if ($behind -gt 0) {
        Write-ColorOutput "⬇️  Behind origin by $behind commits" "Warning"
    }
    if ($ahead -gt 0) {
        Write-ColorOutput "⬆️  Ahead of origin by $ahead commits" "Info"
    }
    if ($behind -eq 0 -and $ahead -eq 0) {
        Write-ColorOutput "✅ Up to date with origin" "Success"
    }
    
    return $true
}

function Get-CommitMessage {
    param([string]$Type, [string]$ProvidedMessage)
    
    if ($ProvidedMessage) {
        return "$Type`: $ProvidedMessage"
    }
    
    Write-Header "Commit Message Builder"
    Write-ColorOutput "Selected Type: $Type" "Info"
    
    # Type descriptions
    $typeDescriptions = @{
        "feature" = "New feature or enhancement"
        "fix" = "Bug fix"
        "docs" = "Documentation changes"
        "refactor" = "Code refactoring without functionality change"
        "test" = "Adding or updating tests"
        "style" = "Code style changes (formatting, etc.)"
        "chore" = "Maintenance tasks, build changes"
        "release" = "Version release"
    }
    
    Write-ColorOutput "📝 $($typeDescriptions[$Type])" "Info"
    
    # Get commit message
    do {
        $message = Read-Host "`n💬 Enter commit description"
        if ([string]::IsNullOrWhiteSpace($message)) {
            Write-ColorOutput "❌ Commit message cannot be empty!" "Error"
        }
    } while ([string]::IsNullOrWhiteSpace($message))
    
    $fullMessage = "$Type`: $message"
    
    # Show preview
    Write-ColorOutput "`n📋 Commit Preview: $fullMessage" "Header"
    
    if (-not $Force) {
        $confirm = Read-Host "✅ Proceed with this commit? (y/N)"
        if ($confirm -ne "y" -and $confirm -ne "Y") {
            Write-ColorOutput "❌ Commit cancelled" "Warning"
            exit 1
        }
    }
    
    return $fullMessage
}

function Invoke-GitCommit {
    param([string]$CommitMessage)
    
    Write-Header "Staging and Committing Changes"
    
    # Check for changes
    $status = git status --porcelain
    if (-not $status) {
        Write-ColorOutput "ℹ️  No changes to commit" "Info"
        return $true
    }
    
    # Show what will be staged
    Write-ColorOutput "📁 Files to be staged:" "Info"
    git status --short
    
    if (-not $Force) {
        $confirm = Read-Host "`n➕ Stage all changes? (Y/n)"
        if ($confirm -eq "n" -or $confirm -eq "N") {
            Write-ColorOutput "❌ Staging cancelled" "Warning"
            return $false
        }
    }
    
    # Stage all changes
    Write-ColorOutput "`n⏳ Staging all changes..." "Info"
    git add -A
    
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput "❌ Failed to stage changes" "Error"
        return $false
    }
    
    # Commit
    Write-ColorOutput "⏳ Committing changes..." "Info"
    git commit -m $CommitMessage
    
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput "❌ Failed to commit changes" "Error"
        return $false
    }
    
    Write-ColorOutput "✅ Changes committed successfully!" "Success"
    return $true
}

function Invoke-GitPush {
    Write-Header "Pushing to GitHub"
    
    # Get current branch
    $currentBranch = git rev-parse --abbrev-ref HEAD
    
    Write-ColorOutput "⏳ Pushing to origin/$currentBranch..." "Info"
    git push origin $currentBranch
    
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput "❌ Failed to push to GitHub" "Error"
        
        # Try to provide helpful error context
        Write-ColorOutput "`n🔍 Possible solutions:" "Info"
        Write-ColorOutput "  1. Check your internet connection" "Info"
        Write-ColorOutput "  2. Verify GitHub authentication (git config --list | grep user)" "Info"
        Write-ColorOutput "  3. Try: git pull origin $currentBranch (if behind)" "Info"
        Write-ColorOutput "  4. Check repository permissions" "Info"
        
        return $false
    }
    
    Write-ColorOutput "✅ Successfully pushed to GitHub!" "Success"
    
    # Get the repository URL for quick access
    $repoUrl = git config --get remote.origin.url
    if ($repoUrl) {
        $repoUrl = $repoUrl -replace "\.git$", "" -replace "git@github.com:", "https://github.com/"
        Write-ColorOutput "🌐 Repository: $repoUrl" "Info"
    }
    
    return $true
}

function Invoke-GitSync {
    Write-Header "Syncing with GitHub"
    
    # Fetch latest changes
    Write-ColorOutput "⏳ Fetching latest changes..." "Info"
    git fetch origin
    
    if ($LASTEXITCODE -ne 0) {
        Write-ColorOutput "❌ Failed to fetch from GitHub" "Error"
        return $false
    }
    
    # Get current branch
    $currentBranch = git rev-parse --abbrev-ref HEAD
    
    # Check if we're behind
    $behind = git rev-list --count HEAD..origin/$currentBranch 2>$null
    if ($behind -gt 0) {
        Write-ColorOutput "⬇️  $behind commits behind. Pulling changes..." "Warning"
        
        # Check for local changes
        $status = git status --porcelain
        if ($status) {
            Write-ColorOutput "⚠️  Local changes detected. Stashing first..." "Warning"
            git stash push -m "Auto-stash before sync $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
            $stashed = $true
        }
        
        # Pull changes
        git pull origin $currentBranch
        
        if ($LASTEXITCODE -ne 0) {
            Write-ColorOutput "❌ Failed to pull changes" "Error"
            return $false
        }
        
        # Restore stashed changes if any
        if ($stashed) {
            Write-ColorOutput "📦 Restoring stashed changes..." "Info"
            git stash pop
        }
    } else {
        Write-ColorOutput "✅ Already up to date with GitHub" "Success"
    }
    
    return $true
}

# Main execution
try {
    Write-Header "Gary AI Plugin - Git Automation"
    Write-ColorOutput "🚀 Action: $Action" "Header"
    
    # Always check status first
    if (-not (Get-GitStatus)) {
        exit 1
    }
    
    switch ($Action) {
        "status" {
            Write-ColorOutput "`n✅ Status check complete!" "Success"
        }
        
        "commit" {
            $commitMessage = Get-CommitMessage -Type $Type -ProvidedMessage $Message
            if (-not (Invoke-GitCommit -CommitMessage $commitMessage)) {
                exit 1
            }
        }
        
        "push" {
            if (-not (Invoke-GitPush)) {
                exit 1
            }
        }
        
        "deploy" {
            $commitMessage = Get-CommitMessage -Type $Type -ProvidedMessage $Message
            if ((Invoke-GitCommit -CommitMessage $commitMessage) -and (Invoke-GitPush)) {
                Write-ColorOutput "`n🎉 Deployment complete!" "Success"
            } else {
                exit 1
            }
        }
        
        "sync" {
            if (-not (Invoke-GitSync)) {
                exit 1
            }
        }
        
        "quick" {
            # Quick commit and push with minimal prompts
            if (-not $Message) {
                $Message = Read-Host "💬 Quick commit message"
            }
            $commitMessage = "$Type`: $Message"
            
            if ((Invoke-GitCommit -CommitMessage $commitMessage) -and (Invoke-GitPush)) {
                Write-ColorOutput "`n⚡ Quick deployment complete!" "Success"
            } else {
                exit 1
            }
        }
    }
    
    Write-ColorOutput "`n🎊 Git automation completed successfully!" "Success"
    
} catch {
    Write-ColorOutput "`n💥 Error: $($_.Exception.Message)" "Error"
    exit 1
} 