<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Activity,
  ExternalLink,
  FileText,
  GitPullRequest,
  Image,
  LayoutGrid,
  MessageSquare,
  Settings,
  Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as { is_admin?: boolean })?.is_admin === true);

const mainNavItems = computed<NavItem[]>(() => {
  const items: NavItem[] = [
    {
      title: 'Dashboard',
      href: dashboard(),
      icon: LayoutGrid,
    },
    {
      title: 'Pages',
      href: '/admin/pages',
      icon: FileText,
    },
    {
      title: 'Media',
      href: '/admin/media',
      icon: Image,
    },
    {
      title: 'Feedback',
      href: '/admin/feedback',
      icon: MessageSquare,
    },
  ];

  if (isAdmin.value) {
    items.push({
      title: 'Users',
      href: '/users',
      icon: Users,
    });
    items.push({
      title: 'Git Sync',
      href: '/admin/git-sync',
      icon: GitPullRequest,
    });
    items.push({
      title: 'Activity Logs',
      href: '/activity-logs',
      icon: Activity,
    });
    items.push({
      title: 'Site Settings',
      href: '/admin/settings',
      icon: Settings,
    });
  }

  return items;
});

const footerNavItems: NavItem[] = [
  {
    title: 'Github',
    href: 'https://github.com/crony-io/docs-starter-kit',
    icon: ExternalLink,
  },
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton class="h-auto w-[228px] max-w-full" as-child>
            <Link :href="dashboard()">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
