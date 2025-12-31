import { inject, type InjectionKey, onUnmounted, provide, ref, type Ref, watch } from 'vue';

export interface SelectContext {
  id: number;
  modelValue: Ref<string | undefined>;
  open: Ref<boolean>;
  disabled: Ref<boolean>;
  placeholder: Ref<string>;
  triggerRef: Ref<HTMLElement | null>;
  contentRef: Ref<HTMLElement | null>;
  triggerId: string;
  contentId: string;
  selectedLabel: Ref<string>;
  updateValue: (value: string, label: string) => void;
  toggle: () => void;
  close: () => void;
}

export const SelectContextKey: InjectionKey<SelectContext> = Symbol('SelectContext');

// Global registry to track all select instances and close others when one opens
const selectRegistry = new Map<number, () => void>();

function closeAllSelectsExcept(exceptId: number) {
  selectRegistry.forEach((closeFn, id) => {
    if (id !== exceptId) {
      closeFn();
    }
  });
}

let selectIdCounter = 0;

export function useSelectRoot(options: {
  modelValue?: Ref<string | undefined>;
  defaultValue?: string;
  disabled?: Ref<boolean>;
  open?: Ref<boolean>;
  defaultOpen?: boolean;
  onUpdateModelValue?: (value: string) => void;
  onUpdateOpen?: (open: boolean) => void;
}) {
  const id = ++selectIdCounter;
  const triggerId = `select-trigger-${id}`;
  const contentId = `select-content-${id}`;

  // Use plain refs for internal state
  const internalValue = ref<string | undefined>(options.defaultValue);
  const internalOpen = ref(options.defaultOpen ?? false);
  const internalDisabled = ref(false);

  const placeholder = ref('');
  const selectedLabel = ref('');
  const triggerRef = ref<HTMLElement | null>(null);
  const contentRef = ref<HTMLElement | null>(null);

  // Sync with external props if provided
  if (options.modelValue) {
    watch(
      () => options.modelValue?.value,
      (val) => {
        internalValue.value = val;
      },
      { immediate: true },
    );
  }

  if (options.disabled) {
    watch(
      () => options.disabled?.value,
      (val) => {
        internalDisabled.value = val ?? false;
      },
      { immediate: true },
    );
  }

  if (options.open) {
    watch(
      () => options.open?.value,
      (val) => {
        if (val !== undefined) {
          internalOpen.value = val;
        }
      },
      { immediate: true },
    );
  }

  const updateValue = (value: string, label: string) => {
    internalValue.value = value;
    selectedLabel.value = label;
    options.onUpdateModelValue?.(value);
    close();
  };

  const toggle = () => {
    if (!internalDisabled.value) {
      const willOpen = !internalOpen.value;
      if (willOpen) {
        // Close all other selects before opening this one
        closeAllSelectsExcept(id);
      }
      internalOpen.value = willOpen;
      options.onUpdateOpen?.(willOpen);
    }
  };

  const close = () => {
    if (internalOpen.value) {
      internalOpen.value = false;
      options.onUpdateOpen?.(false);
    }
  };

  // Register this select's close function in the global registry
  selectRegistry.set(id, close);

  // Cleanup on unmount
  onUnmounted(() => {
    selectRegistry.delete(id);
  });

  const context: SelectContext = {
    id,
    modelValue: internalValue,
    open: internalOpen,
    disabled: internalDisabled,
    placeholder,
    triggerRef,
    contentRef,
    triggerId,
    contentId,
    selectedLabel,
    updateValue,
    toggle,
    close,
  };

  provide(SelectContextKey, context);

  return context;
}

export function useSelectContext() {
  const context = inject(SelectContextKey);
  if (!context) {
    throw new Error('Select components must be used within a Select component');
  }
  return context;
}
