<?php

namespace App\Support;

/**
 * Canonical hook names for plugins and themes.
 *
 * Prefer these identifiers when adding new extension points. Existing hooks use
 * dotted names (e.g. post.content); keep them stable to avoid breaking plugins.
 *
 * One-time migrations and setup should run on plugin.activate / plugin.deactivate,
 * not on every request from index.php.
 */
final class PluginHooks
{
    public const PLUGIN_LOADING = 'plugin.loading';

    public const PLUGIN_LOADED = 'plugin.loaded';

    public const PLUGINS_LOADED = 'plugins.loaded';

    public const PLUGIN_ACTIVATE = 'plugin.activate';

    public const PLUGIN_DEACTIVATE = 'plugin.deactivate';

    public const PLUGIN_REGISTER_ROUTES = 'plugin.register_routes';

    public const THEME_LOADED = 'theme.loaded';

    public const THEME_STYLES = 'theme.styles';

    public const THEME_SCRIPTS = 'theme.scripts';

    public const POST_CONTENT = 'post.content';

    public const POST_META = 'post.meta';

    public const BEFORE_HOME_PAGE = 'before_home_page';

    public const HOME_PAGE_DATA = 'home_page_data';

    public const BEFORE_BLOG_PAGE = 'before_blog_page';

    public const BLOG_POSTS_QUERY = 'blog_posts_query';

    public const BLOG_POSTS_PER_PAGE = 'blog_posts_per_page';

    public const BLOG_PAGE_DATA = 'blog_page_data';

    public const POST_FOUND = 'post_found';

    public const BEFORE_SINGLE_POST = 'before_single_post';

    public const RELATED_POSTS_QUERY = 'related_posts_query';

    public const RELATED_POSTS_COUNT = 'related_posts_count';

    public const SINGLE_POST_DATA = 'single_post_data';

    public const PAGE_FOUND = 'page_found';

    public const BEFORE_PAGE = 'before_page';

    public const PAGE_DATA = 'page_data';

    public const BEFORE_STATIC_PAGE = 'before_static_page';

    public const STATIC_PAGE_DATA = 'static_page_data';

    public const STATIC_PAGE_TEMPLATES = 'static_page_templates';

    public const TEMPLATE_HIERARCHY = 'template_hierarchy';

    public const TEMPLATE_INCLUDE = 'template_include';

    public const THEME_VIEW_PATH = 'theme.view_path';

    public const THEME_ASSET_PATH = 'theme.asset_path';

    public const THEME_PARENT_VIEW_PATH = 'theme.parent_view_path';

    public const THEME_VIEW_PATH_BASE = 'theme.view_path_base';

    public const THEME_PARENT_VIEW_PATH_BASE = 'theme.parent_view_path_base';

    public const THEME_ASSETS_PATH_BASE = 'theme.assets_path_base';

    public const THEME_PUBLIC_ASSETS_PATH = 'theme.public_assets_path';

    public const THEME_CONTENT = 'theme.content';

    public const THEME_BEFORE_ACTIVATE = 'theme.before_activate';

    public const THEME_AFTER_ACTIVATE = 'theme.after_activate';

    public const THEME_BEFORE_DELETE = 'theme.before_delete';

    public const THEME_AFTER_DELETE = 'theme.after_delete';
}
