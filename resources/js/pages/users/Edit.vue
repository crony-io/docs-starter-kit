<script setup lang="ts">
import { edit, index, update } from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, User } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
  user: User;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Gestión de Usuarios',
    href: index().url,
  },
  {
    title: 'Editar Usuario',
    href: edit(props.user.id).url,
  },
];
</script>

<template>
  <Head :title="`Editar Usuario: ${user.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="mb-6 flex items-center gap-4">
        <Button variant="outline" size="sm" as-child>
          <Link :href="index().url">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Volver
          </Link>
        </Button>
        <Heading
          :title="`Editar Usuario: ${user.name}`"
          description="Actualiza la información del usuario"
        />
      </div>

      <div class="mx-auto max-w-2xl rounded-lg border bg-card p-6">
        <Form :action="update(user.id)" v-slot="{ errors, processing, recentlySuccessful }">
          <div class="space-y-6">
            <div class="grid gap-2">
              <Label for="name">Nombre</Label>
              <Input
                id="name"
                name="name"
                :default-value="user.name"
                placeholder="Nombre completo"
                required
                autocomplete="name"
              />
              <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
              <Label for="email">Correo Electrónico</Label>
              <Input
                id="email"
                name="email"
                type="email"
                :default-value="user.email"
                placeholder="correo@ejemplo.com"
                required
                autocomplete="email"
              />
              <InputError :message="errors.email" />
            </div>

            <div class="rounded-lg border border-border bg-muted/50 p-4">
              <h3 class="mb-4 text-sm font-medium">
                Cambiar Contraseña
                <span class="text-muted-foreground">(Opcional)</span>
              </h3>

              <div class="space-y-4">
                <div class="grid gap-2">
                  <Label for="password">Nueva Contraseña</Label>
                  <Input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="••••••••"
                    autocomplete="new-password"
                  />
                  <InputError :message="errors.password" />
                  <p class="text-xs text-muted-foreground">
                    Deja este campo vacío si no deseas cambiar la contraseña
                  </p>
                </div>

                <div class="grid gap-2">
                  <Label for="password_confirmation">Confirmar Nueva Contraseña</Label>
                  <Input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="••••••••"
                    autocomplete="new-password"
                  />
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                  {{ processing ? 'Guardando...' : 'Guardar Cambios' }}
                </Button>

                <Button type="button" variant="outline" as-child>
                  <Link :href="index().url">Cancelar</Link>
                </Button>
              </div>

              <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
              >
                <p v-show="recentlySuccessful" class="text-sm text-green-600">
                  ✓ Guardado correctamente
                </p>
              </Transition>
            </div>
          </div>
        </Form>
      </div>
    </div>
  </AppLayout>
</template>
