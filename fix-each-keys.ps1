# PowerShell script to fix missing keys in Svelte each blocks

$svelteFiles = Get-ChildItem -Path "src" -Filter "*.svelte" -Recurse

foreach ($file in $svelteFiles) {
    Write-Host "Processing: $($file.FullName)"

    $content = Get-Content $file.FullName -Raw
    $originalContent = $content

    # Pattern to match {#each something as item} without (item.id) or (index)
    $pattern = '\{#each\s+([^}]+?)\s+as\s+(\w+)(?:\s*,\s*(\w+))?\}'

    $matches = [regex]::Matches($content, $pattern)

    foreach ($match in $matches) {
        $fullMatch = $match.Value
        $iterable = $match.Groups[1].Value.Trim()
        $itemVar = $match.Groups[2].Value.Trim()
        $indexVar = if ($match.Groups[3].Success) { $match.Groups[3].Value.Trim() } else { $null }

        # Skip if already has a key (contains parentheses)
        if ($fullMatch -match '\([^)]+\)') {
            continue
        }

        # Determine the best key based on common patterns
        $newKey = ""

        # Try to use .id property first
        if ($itemVar -and $content -match "$itemVar\.id\b") {
            $newKey = "($itemVar.id)"
        }
        # Try other common ID patterns
        elseif ($itemVar -and $content -match "$itemVar\.email\b") {
            $newKey = "($itemVar.email)"
        }
        elseif ($itemVar -and $content -match "$itemVar\.instance_date\b") {
            $newKey = "($itemVar.instance_date)"
        }
        elseif ($itemVar -and $content -match "$itemVar\.date\b") {
            $newKey = "($itemVar.date)"
        }
        elseif ($itemVar -and $content -match "$itemVar\.name\b") {
            $newKey = "($itemVar.name)"
        }
        elseif ($itemVar -and $content -match "$itemVar\.title\b") {
            $newKey = "($itemVar.title)"
        }
        # Use index variable if available
        elseif ($indexVar) {
            $newKey = "($indexVar)"
        }
        # Use item itself if it's primitive
        elseif ($itemVar -and ($iterable -match "^[a-zA-Z_][a-zA-Z0-9_]*$" -or $iterable -match "^\[.*\]$")) {
            $newKey = "($itemVar)"
        }
        # Fallback: Create an index variable
        else {
            if ($indexVar) {
                $newKey = "($indexVar)"
            } else {
                $replacement = "{#each $iterable as $itemVar, index ($itemVar)"
                $content = $content -replace [regex]::Escape($fullMatch), $replacement
                continue
            }
        }

        # Apply the replacement
        if ($newKey) {
            if ($indexVar) {
                $replacement = "{#each $iterable as $itemVar, $indexVar $newKey"
            } else {
                $replacement = "{#each $iterable as $itemVar $newKey"
            }
            $content = $content -replace [regex]::Escape($fullMatch), $replacement
        }
    }

    # Write back if changed
    if ($content -ne $originalContent) {
        Set-Content $file.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($file.Name)"
    }
}

Write-Host "Done processing all Svelte files."
