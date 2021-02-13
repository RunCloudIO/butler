#!/bin/sh

set -e

ME=$(basename $0)

auto_envsubst() {
  local template_dir="${NGINX_ENVSUBST_TEMPLATE_DIR:-/etc/nginx/templates}"
  local suffix="${NGINX_ENVSUBST_TEMPLATE_SUFFIX:-.template}"
  local output_dir="${NGINX_ENVSUBST_OUTPUT_DIR:-/etc/nginx/conf.d}"
  local NGINXCONFPATH="/usr/local/openresty/nginx/conf/nginx.conf"

  local template defined_envs relative_path output_path subdir
  defined_envs=$(printf '${%s} ' $(env | cut -d= -f1))
  [ -d "$template_dir" ] || return 0
  if [ ! -w "$output_dir" ]; then
    echo >&2 "$ME: ERROR: $template_dir exists, but $output_dir is not writable"
    return 0
  fi
  find "$template_dir" -follow -type f -name "*$suffix" -print | while read -r template; do
    relative_path="${template#$template_dir/}"
    output_path="$output_dir/${relative_path%$suffix}"
    subdir=$(dirname "$relative_path")
    # create a subdirectory where the template file exists
    mkdir -p "$output_dir/$subdir"
    if [ $template == "/etc/nginx/templates/nginx.conf.template" ]; then
      echo >&2 "$ME: Running envsubst on $template to $NGINXCONFPATH"
      envsubst "$defined_envs" < "$template" > "$NGINXCONFPATH"
    else
      echo >&2 "$ME: Running envsubst on $template to $output_path"
      envsubst "$defined_envs" < "$template" > "$output_path"
    fi
  done
}

auto_envsubst


exit 0