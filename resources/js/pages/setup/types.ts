export type SetupStep = 'admin' | 'mode' | 'git-config' | 'site-settings' | 'review';
export type ContentMode = 'git' | 'cms';

export interface AdminFormData {
  name: string;
  email: string;
  password: string;
  passwordConfirmation: string;
}

export interface GitConfigData {
  repositoryUrl: string;
  branch: string;
  accessToken: string;
  syncFrequency: number;
  webhookSecret: string;
}

export interface SiteSettingsData {
  siteName: string;
  siteTagline: string;
  showFooter: boolean;
  footerText: string;
  metaRobots: 'index' | 'noindex';
}

export interface SetupFormData {
  contentMode: ContentMode | null;
  admin: AdminFormData;
  git: GitConfigData;
  siteSettings: SiteSettingsData;
}

export interface ConnectionTestResult {
  success: boolean;
  message: string;
}
