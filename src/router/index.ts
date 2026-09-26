import { createRouter, createWebHistory } from 'vue-router'
import { getRouterBase } from '@/lib/theme-config'
import { preloadSpecialPage } from '@/lib/special-page-loader'
import { setPageTransition, getPageTransitionDirection } from '@/composables/usePageTransition'
import HomeView from '@/views/HomeView.vue'
import ContentView from '@/views/ContentView.vue'
import GoRedirect from '@/views/GoRedirect.vue'
import ShuoshuoView from '@/views/ShuoshuoView.vue'
import AboutView from '@/views/AboutView.vue'
import ArchivesView from '@/views/ArchivesView.vue'
import LinksView from '@/views/LinksView.vue'

const router = createRouter({
  history: createWebHistory(getRouterBase()),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/categories/:slug', name: 'category', component: HomeView },
    { path: '/go', name: 'go', component: GoRedirect },
    { path: '/shuoshuo', name: 'shuoshuo', component: ShuoshuoView },
    { path: '/about', name: 'about', component: AboutView },
    // Keep the reserved archive landing page exact. Article permalinks may
    // legitimately live below /archives/<slug>/ and must reach ContentView.
    { path: '/archives', name: 'archives', component: ArchivesView },
    { path: '/links', name: 'links', component: LinksView },

    { path: '/:pathMatch(.*)*', name: 'content', component: ContentView },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    // 移动端推进转场由 usePageTransition 钩子接管滚动，避免二者互相抢滚动位置
    if (getPageTransitionDirection() !== 'none') return false
    if (savedPosition) {
      return savedPosition
    }
    return false
  },
})

router.beforeEach(async (to, from) => {
  // 移动端「列表 ⇄ 详情」推进转场：在 DOM 变更前定方向、记滚动位置
  setPageTransition(from, to)
  await preloadSpecialPage(to.path)
})

router.afterEach(() => {
  // 转场接管时由转场钩子负责还原滚动位置，避免此处把离场页顶回顶部
  if (getPageTransitionDirection() !== 'none') return
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

export default router
