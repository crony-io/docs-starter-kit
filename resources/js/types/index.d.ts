import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavItem {
  title: string;
  href: NonNullable<InertiaLinkProps['href']>;
  icon?: LucideIcon;
  isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  sidebarOpen: boolean;
};

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export type PageStatus = 'draft' | 'published' | 'archived';
export type PageSource = 'cms' | 'git';
export type PageType = 'navigation' | 'group' | 'document';

export interface Page {
  id: number;
  title: string;
  slug: string;
  type: PageType;
  icon: string | null;
  content: string | null;
  status: PageStatus;
  order: number;
  parent_id: number | null;
  is_default: boolean;
  is_expanded: boolean;
  seo_title: string | null;
  seo_description: string | null;
  source: PageSource;
  git_path: string | null;
  git_last_commit: string | null;
  git_last_author: string | null;
  updated_at_git: string | null;
  created_by: number | null;
  created_at: string;
  updated_at: string;
  full_path?: string;
  author?: User;
  parent?: Page;
  children?: Page[];
  children_count?: number;
  versions?: PageVersion[];
}

export interface PageVersion {
  id: number;
  page_id: number;
  content: string;
  version_number: number;
  created_by: number | null;
  created_at: string;
  updated_at: string;
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: PaginationLink[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface StatusOption {
  value: string;
  label: string;
}

export interface SiteSettings {
  siteName: string;
  siteTagline: string;
  logoLight: string | null;
  logoDark: string | null;
  favicon: string | null;
  theme: {
    primaryColor: string;
    secondaryColor: string;
    accentColor: string;
    darkMode: 'light' | 'dark' | 'system';
    customCss: string;
  };
  typography: {
    headingFont: string;
    bodyFont: string;
    codeFont: string;
    baseFontSize: number;
    lineHeight: number;
  };
  layout: {
    sidebarWidth: number;
    contentWidth: number;
    showToc: boolean;
    tocPosition: 'left' | 'right';
    showBreadcrumbs: boolean;
    showFooter: boolean;
    footerText: string;
  };
  social: {
    twitter: string;
    github: string;
    discord: string;
    linkedin: string;
  };
  advanced: {
    searchEnabled: boolean;
    codeCopyButton: boolean;
    codeLineNumbers: boolean;
    llmTxtEnabled: boolean;
  };
}
