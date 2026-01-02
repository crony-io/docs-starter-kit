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
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as feedbackIndex } from '@/routes/admin/feedback';
import { index as gitSyncIndex } from '@/routes/admin/git-sync';
import { index as mediaIndex } from '@/routes/admin/media';
import { index as pagesIndex } from '@/routes/admin/pages';
import { index as settingsIndex } from '@/routes/admin/settings';
import { index as usersIndex } from '@/routes/users';
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
      href: pagesIndex(),
      icon: FileText,
    },
    {
      title: 'Media',
      href: mediaIndex(),
      icon: Image,
    },
    {
      title: 'Feedback',
      href: feedbackIndex(),
      icon: MessageSquare,
    },
  ];

  if (isAdmin.value) {
    items.push({
      title: 'Users',
      href: usersIndex(),
      icon: Users,
    });
    items.push({
      title: 'Git Sync',
      href: gitSyncIndex(),
      icon: GitPullRequest,
    });
    items.push({
      title: 'Activity Logs',
      href: activityLogsIndex(),
      icon: Activity,
    });
    items.push({
      title: 'Site Settings',
      href: settingsIndex(),
      icon: Settings,
    });
  }

  return items;
});

const footerNavItems: NavItem[] = [
  {
    title: 'Frontend Docs',
    href: '/',
    icon: FileText,
  },
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
