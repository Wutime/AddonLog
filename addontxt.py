#!/usr/bin/env python3
import os

EXCLUDE_DIRS = {'vendor', '_data', '_releases'}
EXCLUDE_FILES = {
    './AI-dialog.txt',
    './README.md'
}
ALL_EXTENSIONS = {'.json', '.js', '.php', '.css', '.txt', '.xml', '.less', '.html'}
MAX_FILE_SIZE = 10 * 1024 * 1024  # 10 MB

def should_include(file_path, output_file, extensions):
    norm_path = os.path.normpath(file_path)
    if any(part in EXCLUDE_DIRS for part in norm_path.split(os.sep)):
        return False
    if os.path.normpath(file_path) == os.path.normpath(output_file):
        return False  # Exclude the output file itself
    if norm_path in EXCLUDE_FILES:
        return False
    _, ext = os.path.splitext(file_path)
    return ext.lower() in extensions

def main():
    print("Select output mode:")
    print("1. Normal (all supported extensions)")
    print("2. PHP files only")
    choice = input("Enter 1 or 2: ").strip()

    if choice == '2':
        extensions = {'.php'}
        print("Running in PHP-only mode.")
    else:
        extensions = ALL_EXTENSIONS
        print("Running in normal mode.")

    addon_name = os.path.basename(os.getcwd())
    output_file = f"{addon_name}.txt"

    with open(output_file, 'w', encoding='utf-8') as out:
        for root, dirs, files in os.walk('.'):
            dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]

            for file in files:
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, '.')

                if not should_include(rel_path, output_file, extensions):
                    continue

                try:
                    size = os.path.getsize(full_path)
                    if size > MAX_FILE_SIZE:
                        print(f"Skipping large file: {full_path} ({size} bytes)")
                        continue
                except Exception:
                    pass  # ignore size errors, try to read anyway

                out.write(f"File: {rel_path}\nz\n")
                try:
                    with open(full_path, 'r', encoding='utf-8') as f:
                        for line in f:
                            out.write(line)
                except Exception as e:
                    out.write(f"[Error reading file: {e}]\n")
                out.write("\n\n")

    print(f"Created text file: {output_file}")

if __name__ == "__main__":
    main()
