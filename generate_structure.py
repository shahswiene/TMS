import os
import argparse


def generate_directory_structure(startpath, output_file):
    with open(output_file, "w", encoding="utf-8") as f:
        for root, dirs, files in os.walk(startpath):
            # Skip .git directory
            dirs[:] = [d for d in dirs if d != ".git"]

            level = root.replace(startpath, "").count(os.sep)
            indent = "│   " * (level - 1) + "├── " if level > 0 else ""
            folder_name = (
                os.path.basename(root)
                if level > 0
                else os.path.basename(os.path.abspath(startpath))
            )
            f.write(f"{indent}{folder_name}/\n")
            for file in files:
                if file != os.path.basename(output_file):
                    sub_indent = "│   " * level + "├── "
                    f.write(f"{sub_indent}{file}\n")


def main():
    parser = argparse.ArgumentParser(description="Generate project file structure")
    parser.add_argument("--path", default=".", help="Path to the project directory")
    parser.add_argument(
        "--output", default="project_file_structure.txt", help="Output file name"
    )
    args = parser.parse_args()

    generate_directory_structure(args.path, args.output)
    print(f"Project structure has been written to {args.output}")


if __name__ == "__main__":
    main()
