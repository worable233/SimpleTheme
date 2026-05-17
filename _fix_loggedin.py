import os, sys

php_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'functions.php')

with open(php_file, 'r', encoding='utf-8') as f:
    content = f.read()

# Add currentUser to site info response
old = (
    "\t\t\t'endNote'        => ! empty( $theme_options['end_note'] ) ? $theme_options['end_note'] : '',\n"
    "\t\t),\n"
)

new = (
    "\t\t\t'endNote'        => ! empty( $theme_options['end_note'] ) ? $theme_options['end_note'] : '',\n"
    "\t\t\t'currentUser'    => simple_theme_get_current_commenter(),\n"
    "\t\t),\n"
)

idx = content.find(old)
if idx < 0:
    print('ERROR: endNote line not found!')
    idx2 = content.find("'endNote'")
    if idx2 >= 0:
        print('Found at idx2:', idx2)
        print(repr(content[idx2:idx2+150]))
    sys.exit(1)

content = content.replace(old, new, 1)
print('Added currentUser to site info response')

# Add the helper function before simple_theme_get_site_info
old2 = "function simple_theme_get_site_info() {"
new2 = (
    "function simple_theme_get_current_commenter() {\n"
    "\t$user = wp_get_current_user();\n"
    "\tif ( $user->ID === 0 ) {\n"
    "\t\treturn null;\n"
    "\t}\n"
    "\n"
    "\treturn array(\n"
    "\t\t'displayName' => $user->display_name,\n"
    "\t\t'email'       => $user->user_email,\n"
    "\t\t'url'         => $user->user_url,\n"
    "\t);\n"
    "}\n"
    "\n"
    "function simple_theme_get_site_info() {"
)

if old2 not in content:
    print('ERROR: old2 not found!')
    sys.exit(1)

content = content.replace(old2, new2, 1)
print('Added simple_theme_get_current_commenter function')

with open(php_file, 'w', encoding='utf-8') as f:
    f.write(content)

print('Done!')
