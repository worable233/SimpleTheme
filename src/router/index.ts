import { createRouter, createWebHistory } from 'vue-router'
import { getRouterBase } from '@/lib/theme-config'
import HomeView from '@/views/HomeView.vue'
import ContentView from '@/views/ContentView.vue'
import GoRedirect from '@/views/GoRedirect.vue'

const router = createRouter({
  history: createWebHistory(getRouterBase()),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/go', name: 'go', component: GoRedirect },
    { path: '/category/:slug', redirect: (to) => ({ path: '/', query: { category: to.params.slug as string } }) },
    { path: '/:pathMatch(.*)*', name: 'content', component: ContentView },
  ],
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }
    return false
  },
})

router.afterEach(() => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
})

export default router
