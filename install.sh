#!/usr/bin/env bash

set -e

PLUGIN_NAME="wp-featureflags"
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

    mkdir -p "$plugin_path"
}

copy_config_file() {
    local source_dir="$1"
    local plugin_dir="$2"
    local base_name="$3"
    local local_name="${base_name%.php}.local.php"

    replace_file "$source_dir" "$plugin_dir" "$base_name"

    if [ ! -e "$plugin_dir/$local_name" ]; then
      cp "$source_dir/$local_name" "$plugin_dir/$local_name"
    fi
}

replace_file() {
    local source_dir="$1"
    local plugin_dir="$2"
    local base_name="$3"
    local source_path="$source_dir/$base_name"
    local target_path="$plugin_dir/$base_name"

    if [ -f "$source_path" ]; then
        if [ -f "$target_path" ]; then
            rm "$target_path"
        fi

        cp "$source_path" "$target_path"
    fi
}

copy_plugin_files() {
    local source_dir="$1"
    local target_project="$2"
    local plugin_path="$target_project/$PLUGINS_ROOT/$PLUGIN_NAME"

    replace_file "$source_dir" "$plugin_path" "plugin.php"

    copy_config_file "$source_dir" "$plugin_path" "flags.php"
    copy_config_file "$source_dir" "$plugin_path" "actions.php"
    copy_config_file "$source_dir" "$plugin_path" "snippets.php"
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
