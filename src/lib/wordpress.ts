/**
 * wordpress.ts — WordPress API 客户端（组合导出）
 *
 * 提供所有 WordPress 相关 API 函数的统一入口。
 * 按功能域拆分在 api-*.ts 子模块中，本文件仅做 re-export。
 */

// Re-export all public API functions from sub-modules
export { apiClient, buildRestUrl } from './api-client'
export { fetchCacheVersion, fetchSiteInfo, fetchAboutInfo, fetchNavigation, resolveThemePath, fetchCategories, fetchLinks } from './api-site'
export { fetchLatestPosts, fetchCollection, fetchPostCollectionByTaxonomy, fetchPostCollectionByDate, fetchContentByRestUrl, trackPostView, fetchPage, toRouterPathFromWpLink } from './api-posts'
export { fetchComments, createComment, likeComment, fetchCaptcha, pinComment, deleteComment, fetchUserPendingComments } from './api-comments'
export { getErrorMessage } from './api-utils'
