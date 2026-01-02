export type SetupStep = 'admin' | 'mode' | 'git-config' | 'review';
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

export interface SetupFormData {
  contentMode: ContentMode | null;
  admin: AdminFormData;
  git: GitConfigData;
}

export interface ConnectionTestResult {
  success: boolean;
  message: string;
}
