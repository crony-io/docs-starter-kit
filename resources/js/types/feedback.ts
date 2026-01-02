/**
 * Structured form_data that includes responses and field definitions snapshot
 */
export interface StructuredFormData {
  form_name: string | null;
  fields: FeedbackFormField[];
  responses: Record<string, unknown>;
}

export interface FeedbackResponse {
  id: number;
  page_id: number;
  feedback_form_id: number | null;
  is_helpful: boolean;
  form_data: StructuredFormData | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  updated_at: string;
  page?: { id: number; title: string; slug: string };
  feedback_form?: { id: number; name: string };
}

export interface PageOption {
  id: number;
  title: string;
  slug: string;
}

export interface PageStat {
  page_id: number;
  page_title: string;
  page_slug: string;
  total: number;
  helpful_count: number;
  helpfulness_score: number;
}

export interface FeedbackStats {
  total: number;
  helpful: number;
  notHelpful: number;
  helpfulPercentage: number;
  todayCount: number;
}

export interface FeedbackForm {
  id: number;
  name: string;
  trigger_type: 'positive' | 'negative' | 'always';
  fields: FeedbackFormField[];
  is_active: boolean;
  responses_count?: number;
}

export interface FeedbackFormField {
  type: 'text' | 'textarea' | 'radio' | 'checkbox' | 'rating' | 'email';
  label: string;
  required: boolean;
  options?: string[];
}
