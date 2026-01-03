<script setup lang="ts">
import {
  destroy,
  edit,
  index,
  store,
} from '@/actions/App/Http/Controllers/UserManagementController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PaginationLink, User } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
  users: {
    data: User[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
  };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Gestión de Usuarios',
    href: index().url,
  },
];

const isCreateModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);
const deletingUserId = ref<number | null>(null);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const openDeleteDialog = (user: User) => {
  userToDelete.value = user;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = () => {
  if (!userToDelete.value) {
    return;
  }

  deletingUserId.value = userToDelete.value.id;
  router.delete(destroy(userToDelete.value.id).url, {
    onFinish: () => {
      deletingUserId.value = null;
      isDeleteDialogOpen.value = false;
      userToDelete.value = null;
    },
  });
};
</script>

<template>
  <Head title="Gestión de Usuarios" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6">
      <div class="flex items-center justify-between">
        <Heading title="Gestión de Usuarios" description="Administra los usuarios del sistema" />
        <Button @click="isCreateModalOpen = true" variant="outline">
          <UserPlus class="mr-2 h-4 w-4" />
          Crear Usuario
        </Button>
      </div>

      <div class="mt-6 rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>Nombre</TableHead>
              <TableHead>Correo Electrónico</TableHead>
              <TableHead>Fecha de Registro</TableHead>
              <TableHead class="text-right">Acciones</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in users.data" :key="user.id">
              <TableCell class="font-medium">{{ user.id }}</TableCell>
              <TableCell>{{ user.name }}</TableCell>
              <TableCell>{{ user.email }}</TableCell>
              <TableCell>{{ formatDate(user.created_at) }}</TableCell>
              <TableCell class="text-right">
                <div class="flex items-center justify-end gap-2">
                  <Button variant="outline" size="sm" as-child>
                    <Link :href="edit(user.id)" class="inline-flex items-center gap-1">
                      <Pencil class="h-3 w-3" />
                      Editar
                    </Link>
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    :disabled="user.id === 1 || deletingUserId === user.id"
                    @click="openDeleteDialog(user)"
                  >
                    <Trash2 class="mr-1 h-3 w-3" />
                    {{ deletingUserId === user.id ? 'Eliminando...' : 'Eliminar' }}
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Pagination -->
        <div v-if="users.links" class="border-t p-4">
          <nav class="flex items-center justify-between">
            <div class="flex flex-1 justify-between sm:hidden">
              <Link
                v-if="users.links[0]?.url"
                :href="users.links[0].url!"
                class="relative inline-flex items-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
              >
                Anterior
              </Link>
              <Link
                v-if="users.links[users.links.length - 1]?.url"
                :href="users.links[users.links.length - 1].url!"
                class="relative ml-3 inline-flex items-center rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-accent"
              >
                Siguiente
              </Link>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-muted-foreground">
                  Mostrando
                  <span class="font-medium">{{ users.from || 0 }}</span>
                  a
                  <span class="font-medium">{{ users.to || 0 }}</span>
                  de
                  <span class="font-medium">{{ users.total }}</span>
                  usuarios
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                  <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                      'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                      link.active
                        ? 'z-10 border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-background text-foreground hover:bg-accent',
                      'first:rounded-l-md last:rounded-r-md',
                    ]"
                  >
                    {{ link.label }}
                  </Link>
                </nav>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog v-model:open="isDeleteDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle> ¿Estás seguro de eliminar este usuario? </AlertDialogTitle>
          <AlertDialogDescription>
            Esta acción no se puede deshacer. Se eliminará permanentemente el usuario
            <strong v-if="userToDelete">{{ userToDelete.name }}</strong>
            del sistema.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel :disabled="deletingUserId !== null"> Cancelar </AlertDialogCancel>
          <AlertDialogAction
            @click="confirmDelete"
            :disabled="deletingUserId !== null"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
          >
            {{ deletingUserId ? 'Eliminando...' : 'Eliminar' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Create User Modal -->
    <Dialog v-model:open="isCreateModalOpen">
      <DialogContent class="sm:max-w-[500px]">
        <Form
          :action="store()"
          resetOnSuccess
          @success="isCreateModalOpen = false"
          v-slot="{ errors, processing }"
        >
          <DialogHeader>
            <DialogTitle>Crear Nuevo Usuario</DialogTitle>
            <DialogDescription>
              Completa el formulario para crear un nuevo usuario en el sistema.
            </DialogDescription>
          </DialogHeader>

          <div class="grid gap-4 py-4">
            <div class="grid gap-2">
              <Label for="name">Nombre</Label>
              <Input
                id="name"
                name="name"
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
                placeholder="correo@ejemplo.com"
                required
                autocomplete="email"
              />
              <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
              <Label for="password">Contraseña</Label>
              <Input
                id="password"
                name="password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
              />
              <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
              <Label for="password_confirmation">Confirmar Contraseña</Label>
              <Input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
              />
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="isCreateModalOpen = false"
              :disabled="processing"
            >
              Cancelar
            </Button>
            <Button type="submit" :disabled="processing">
              {{ processing ? 'Creando...' : 'Crear Usuario' }}
            </Button>
          </DialogFooter>
        </Form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
