import { createRouter, createWebHistory } from 'vue-router'
import { getRouterBase } from '@/lib/theme-config'
import { preloadSpecialPage } from '@/lib/special-page-loader'
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
    { path: '/shuoshuo/:pathMatch(.*)*', name: 'shuoshuo', component: ShuoshuoView },
    { path: '/about/:pathMatch(.*)*', name: 'about', component: AboutView },
    // Keep the reserved archive landing page exact. Article permalinks may
    // legitimately live below /archives/<slug>/ and must reach ContentView.
    { path: '/archives', name: 'archives', component: ArchivesView },
    { path: '/links/:pathMatch(.*)*', name: 'links', component: LinksView },

    { path: '/:pathMatch(.*)*', name: 'content', component: ContentView },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return false
  },
})

router.beforeEach(async (to) => {
  await preloadSpecialPage(to.path)
})

router.afterEach(() => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

export default router
