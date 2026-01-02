export type PageStatus = 'draft' | 'published' | 'archived';
export type PageSource = 'cms' | 'git';
export type PageType = 'navigation' | 'group' | 'document';

export interface PageTreeItem {
  id: number;
  title: string;
  slug: string;
  type: PageType;
  status: PageStatus;
  updated_at: string;
  children: PageTreeItem[];
}

export interface MoveValidationResult {
  valid: boolean;
  reason?: string;
}

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
  children?: Page[];
  children_count?: number;
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

export interface PageTreeItemEmits {
  delete: [item: PageTreeItem];
  duplicate: [item: PageTreeItem];
  publish: [item: PageTreeItem];
  unpublish: [item: PageTreeItem];
  select: [id: number, selected: boolean];
  quickCreate: [parentId: number];
}
