#!/usr/bin/env node
// Cross-Plattform-Dispatcher fuer die package.json "scripts"-Eintraege: waehlt je nach
// process.platform die PowerShell- (.ps1, Windows) oder Bash-Fassung (.sh, macOS/Linux)
// desselben Skripts und reicht Argumente durch. Uebersetzt dabei "--kebab-case"-Flags 1:1 in
// PowerShells "-PascalCase"-Parameternamen (die .sh-Skripte verwenden konsequent die
// kebab-case-Form des jeweiligen .ps1-Parameternamens, siehe docs/entscheidungen.md), sodass ein
// package.json-Scripts-Eintrag auf beiden Plattformen identisch bleibt, statt zwei parallele
// Skript-Namen pro Aufgabe pflegen zu muessen.
//
// Nutzung: node scripts/run.mjs <script-name> [--flag value ...]
// Beispiel: node scripts/run.mjs sync-theme-version --no-git-add
//           -> Windows: powershell -File scripts/sync-theme-version.ps1 -NoGitAdd
//           -> macOS/Linux: bash scripts/sync-theme-version.sh --no-git-add

import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";
import path from "node:path";

const scriptsDir = path.dirname(fileURLToPath(import.meta.url));
const [, , name, ...args] = process.argv;

if (!name) {
    console.error("Usage: node scripts/run.mjs <script-name> [args...]");
    process.exit(1);
}

function toPascalFlag(kebabFlag) {
    return kebabFlag
        .replace(/^--/, "")
        .split("-")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join("");
}

const isWindows = process.platform === "win32";

let command;
let commandArgs;

if (isWindows) {
    const psArgs = args.map((arg) => (arg.startsWith("--") ? `-${toPascalFlag(arg)}` : arg));
    command = "powershell";
    commandArgs = [
        "-NoProfile",
        "-ExecutionPolicy",
        "Bypass",
        "-File",
        path.join(scriptsDir, `${name}.ps1`),
        ...psArgs,
    ];
} else {
    command = "bash";
    commandArgs = [path.join(scriptsDir, `${name}.sh`), ...args];
}

const result = spawnSync(command, commandArgs, { stdio: "inherit" });

if (result.error) {
    console.error(result.error.message);
    process.exit(1);
}

process.exit(result.status ?? 1);
