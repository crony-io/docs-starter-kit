export interface SidebarItem {
  id: number;
  title: string;
  slug: string;
  type: 'navigation' | 'group' | 'document';
  icon: string | null;
  path: string;
  isExpanded: boolean;
  children?: SidebarItem[];
}

export interface TocItem {
  id: string;
  text: string;
  level: number;
}

export interface NavigationTab {
  id: number;
  title: string;
  slug: string;
  icon: string | null;
  is_default: boolean;
}

export interface BreadcrumbItem {
  title: string;
  path: string;
  type: 'navigation' | 'group' | 'document';
}
