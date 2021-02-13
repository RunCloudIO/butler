#!/bin/sh
# vim:sw=4:ts=4:et

set -e

echo $1
exec 3>&1

template_dir="/etc/dnsmasq-templates"
output_dir="/etc/dnsmasq.d"
suffix=".template"

defined_envs=$(printf '${%s} ' $(env | cut -d= -f1))

find "$template_dir" -follow -type f -name "*$suffix" -print | while read -r template; do
    relative_path="${template#$template_dir/}"
    output_path="$output_dir/${relative_path%$suffix}"
    subdir=$(dirname "$relative_path")
    # create a subdirectory where the template file exists
    mkdir -p "$output_dir/$subdir"
    echo >&2 "$ME: Running envsubst on $template to $output_path"
    envsubst "$defined_envs" < "$template" > "$output_path"
done

echo >&2 "$0: Configuration complete; ready for start up"

exec "$@"