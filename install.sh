#!/usr/bin/env bash

set -e

PLUGIN_NAME="wc-pp-featureflags"
PLUGINS_ROOT=".ddev/wordpress/wp-content/plugins"

show_usage() {
    echo "Usage: $0 <absolute_path_to_target_project>"
    exit 1
}

validate_target_project() {
    local target_project="$1"
    if [ ! -d "$target_project" ]; then
        echo "Error: The provided path does not exist."
        exit 1
    fi

    if [ ! -d "$target_project/$PLUGINS_ROOT" ]; then
        echo "Error: The plugins folder ($PLUGINS_ROOT) does not exist in the target project."
        exit 1
    fi
}

prepare_plugin_directory() {
    local target_project="$1"
    local plugin_path="$target_project/$PLUGINS_ROOT/$PLUGIN_NAME"

    if [ -d "$plugin_path" ]; then
        rm -rf "$plugin_path"
    fi
    mkdir -p "$plugin_path"
}

copy_plugin_files() {
    local source_dir="$1"
    local target_project="$2"
    local plugin_path="$target_project/$PLUGINS_ROOT/$PLUGIN_NAME"

    cp "$source_dir/plugin.php" "$plugin_path/plugin.php"
    cp "$source_dir/config.php" "$plugin_path/config.php"
}

main() {
    if [ "$#" -ne 1 ]; then
        show_usage
    fi

    local target_project="$1"
    validate_target_project "$target_project"

    prepare_plugin_directory "$target_project"

    local script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    copy_plugin_files "$script_dir" "$target_project"

    echo "Plugin installed successfully in '$target_project'"
}

main "$@"