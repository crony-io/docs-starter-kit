<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { clean, exportMethod, index, show } from '@/routes/activity-logs';
import type { BreadcrumbItem, PaginationLink, User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Filter, Trash2 } from 'lucide-vue-next';
import { reactive, ref, watch } from 'vue';

interface ActivityLog {
  id: number;
  user_id?: number;
  action: string;
  route_name?: string;
  url: string;
  method: string;
  ip_address: string;
  user_agent?: string;
  status_code?: number;
  execution_time?: number;
  controller?: string;
  controller_action?: string;
  description: string;
  created_at: string;
  user?: User;
}

interface Stats {
  total: number;
  today: number;
  errors: number;
  success: number;
  redirects: number;
}

interface FilterOptions {
  actions: string[];
  routes: string[];
  users: Record<number, string>;
}

interface FilterValues {
  user_id: string | number;
  action: string;
  route_name: string;
  start_date: string;
  end_date: string;
  successful: boolean;
  with_errors: boolean;
  per_page: number;
}

interface Props {
  logs: {
    data: ActivityLog[];
    links: PaginationLink[];
    from: number;
    to: number;
    total: number;
  };
  stats: Stats;
  filterOptions: FilterOptions;
  filters: Partial<FilterValues>;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Logs de Actividad',
    href: index().url,
  },
];

const props = defineProps<Props>();

const filters = reactive({
  user_id: props.filters.user_id || '',
  action: props.filters.action || '',
  route_name: props.filters.route_name || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
  successful: props.filters.successful || false,
  with_errors: props.filters.with_errors || false,
  per_page: props.filters.per_page || 15,
});

const showFilters = ref(false);
const isExporting = ref(false);
const isCleaning = ref(false);
const cleanDays = ref(90);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('es-ES');
};

const getWeekStart = () => {
  const now = new Date();
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1);
  return new Date(now.setDate(diff)).toISOString().split('T')[0];
};

const formatExecutionTime = (time?: number) => {
  if (!time) {
    return 'N/A';
  }
  return `${time}ms`;
};

const applyFilters = () => {
  // Remove empty values from filters before sending
  const cleanedFilters = Object.fromEntries(
    Object.entries(filters).filter(([key, value]) => {
      // Keep per_page always
      if (key === 'per_page') {
        return true;
      }
      // For boolean filters, only keep if true (false means no filter)
      if (typeof value === 'boolean') {
        return value === true;
      }
      // Remove empty strings and null/undefined
      return value !== '' && value !== null && value !== undefined;
    }),
  );

  router.get(index(), cleanedFilters, {
    preserveState: true,
    replace: true,
  });
};

const clearFilters = () => {
  filters.user_id = '';
  filters.action = '';
  filters.route_name = '';
  filters.start_date = '';
  filters.end_date = '';
  filters.successful = false;
  filters.with_errors = false;
  filters.per_page = 15;
  applyFilters();
};

const exportLogs = () => {
  isExporting.value = true;
  window.location.href = exportMethod.url({
    query: filters,
  });
};

const cleanLogs = () => {
  if (
    confirm(
      `¿Estás seguro de que quieres eliminar los logs más antiguos de ${cleanDays.value} días?`,
    )
  ) {
    isCleaning.value = true;
    router.post(
      clean(),
      { days: cleanDays.value },
      {
        onFinish: () => {
          isCleaning.value = false;
          router.reload();
        },
      },
    );
  }
};

watch(
  filters,
  () => {
    applyFilters();
  },
  { deep: true },
);
</script>

<template>
  <Head title="Logs de Actividad" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <Heading
          title="Logs de Actividad"
          description="Registro de todas las acciones realizadas en el sistema"
        />
        <div class="flex gap-2">
          <Button variant="outline" size="sm" @click="showFilters = !showFilters">
            <Filter :size="16" class="mr-2" />
            {{ showFilters ? 'Ocultar Filtros' : 'Mostrar Filtros' }}
          </Button>
          <Button variant="default" size="sm" @click="exportLogs" :disabled="isExporting">
            <Download :size="16" class="mr-2" />
            {{ isExporting ? 'Exportando...' : 'Exportar CSV' }}
          </Button>
        </div>
      </div>

      <!-- Quick Filters -->
      <div class="flex flex-wrap gap-2">
        <Button
          :variant="
            !filters.start_date && !filters.with_errors && !filters.successful
              ? 'default'
              : 'outline'
          "
          size="sm"
          @click="clearFilters"
        >
          All
        </Button>
        <Button
          :variant="
            filters.start_date === new Date().toISOString().split('T')[0] ? 'default' : 'outline'
          "
          size="sm"
          @click="
            filters.start_date = new Date().toISOString().split('T')[0];
            filters.end_date = new Date().toISOString().split('T')[0];
          "
        >
          Today
        </Button>
        <Button
          :variant="filters.start_date === getWeekStart() ? 'default' : 'outline'"
          size="sm"
          @click="
            filters.start_date = getWeekStart();
            filters.end_date = '';
          "
        >
          This Week
        </Button>
        <Button
          :variant="filters.with_errors ? 'default' : 'outline'"
          size="sm"
          @click="
            filters.with_errors = !filters.with_errors;
            filters.successful = false;
          "
        >
          Errors Only
        </Button>
        <Button
          :variant="filters.successful ? 'default' : 'outline'"
          size="sm"
          @click="
            filters.successful = !filters.successful;
            filters.with_errors = false;
          "
        >
          Successful Only
        </Button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <Card>
          <CardContent class="py-4">
            <div class="text-2xl font-bold text-primary">
              {{ stats.total.toLocaleString() }}
            </div>
            <p class="text-sm text-muted-foreground">Total de Logs</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="py-4">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
              {{ stats.today.toLocaleString() }}
            </div>
            <p class="text-sm text-green-600/70 dark:text-green-400/70">Hoy</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="py-4">
            <div class="text-2xl font-bold text-destructive">
              {{ stats.errors.toLocaleString() }}
            </div>
            <p class="text-sm text-destructive/70">Errores</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="py-4">
            <div class="text-2xl font-bold text-primary">
              {{ stats.redirects.toLocaleString() }}
            </div>
            <p class="text-sm text-primary/70">Redirecciones</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="py-4">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ stats.success.toLocaleString() }}
            </div>
            <p class="text-sm text-emerald-600/70 dark:text-emerald-400/70">Exitosos</p>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card v-if="showFilters" class="py-8">
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="space-y-2">
              <Label for="user-filter">Usuario</Label>
              <select
                id="user-filter"
                v-model="filters.user_id"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm [&>option]:bg-background [&>option]:text-foreground"
              >
                <option value="">Todos los usuarios</option>
                <option v-for="(name, id) in filterOptions.users" :key="id" :value="id">
                  {{ name }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="action-filter">Acción</Label>
              <select
                id="action-filter"
                v-model="filters.action"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm [&>option]:bg-background [&>option]:text-foreground"
              >
                <option value="">Todas las acciones</option>
                <option v-for="action in filterOptions.actions" :key="action" :value="action">
                  {{ action }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="route-filter">Ruta</Label>
              <select
                id="route-filter"
                v-model="filters.route_name"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm [&>option]:bg-background [&>option]:text-foreground"
              >
                <option value="">Todas las rutas</option>
                <option v-for="route in filterOptions.routes" :key="route" :value="route">
                  {{ route }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="start-date">Fecha Inicio</Label>
              <Input id="start-date" v-model="filters.start_date" type="date" />
            </div>
            <div class="space-y-2">
              <Label for="end-date">Fecha Fin</Label>
              <Input id="end-date" v-model="filters.end_date" type="date" />
            </div>
            <div class="space-y-2">
              <Label for="per-page">Resultados por página</Label>
              <select
                id="per-page"
                v-model="filters.per_page"
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm [&>option]:bg-background [&>option]:text-foreground"
              >
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label class="text-sm font-medium">Estado de Respuesta</Label>
              <div class="flex flex-col gap-3">
                <div class="flex items-center space-x-2">
                  <Checkbox id="successful" v-model="filters.successful" />
                  <Label for="successful" class="cursor-pointer text-sm font-normal">
                    Solo exitosos (200-299)
                  </Label>
                </div>
                <div class="flex items-center space-x-2">
                  <Checkbox id="with-errors" v-model="filters.with_errors" />
                  <Label for="with-errors" class="cursor-pointer text-sm font-normal">
                    Solo con errores (400+)
                  </Label>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-4">
            <Button variant="secondary" size="sm" @click="clearFilters"> Limpiar Filtros </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Clean Logs Section -->
      <Card
        class="border-yellow-200 bg-yellow-50/50 dark:border-yellow-900/50 dark:bg-yellow-900/20"
      >
        <CardContent class="py-8">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                Limpieza de Logs
              </h3>
              <p class="text-sm text-yellow-700 dark:text-yellow-300">
                Eliminar logs antiguos para liberar espacio
              </p>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="cleanDays"
                type="number"
                min="1"
                max="365"
                class="w-20 rounded-md border px-2 py-1"
              />
              <span class="text-sm text-yellow-700 dark:text-yellow-300">días</span>
              <Button
                variant="default"
                size="sm"
                @click="cleanLogs"
                :disabled="isCleaning"
                class="bg-yellow-600 hover:bg-yellow-700"
              >
                <Trash2 :size="16" class="mr-2" />
                {{ isCleaning ? 'Limpiando...' : 'Limpiar' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Logs Table -->
      <Card>
        <CardContent class="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Usuario</TableHead>
                <TableHead>Acción</TableHead>
                <TableHead>Método</TableHead>
                <TableHead>URL</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead>Tiempo</TableHead>
                <TableHead>Fecha</TableHead>
                <TableHead>Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="log in logs.data" :key="log.id">
                <TableCell>
                  <div class="text-sm font-medium">
                    {{ log.user?.name || 'Anónimo' }}
                  </div>
                  <div class="text-sm text-muted-foreground">
                    {{ log.ip_address }}
                  </div>
                </TableCell>
                <TableCell>
                  <div class="text-sm">{{ log.action }}</div>
                  <div class="text-sm text-muted-foreground">
                    {{ log.route_name || 'N/A' }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge :variant="log.method === 'GET' ? 'secondary' : 'default'">
                    {{ log.method }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div class="max-w-xs truncate text-sm">
                    {{ log.url }}
                  </div>
                </TableCell>
                <TableCell>
                  <Badge
                    v-if="log.status_code"
                    :variant="
                      log.status_code >= 200 && log.status_code < 300 ? 'default' : 'destructive'
                    "
                  >
                    {{ log.status_code }}
                  </Badge>
                  <span v-else class="text-muted-foreground">N/A</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                  {{ formatExecutionTime(log.execution_time) }}
                </TableCell>
                <TableCell class="text-muted-foreground">
                  {{ formatDate(log.created_at) }}
                </TableCell>
                <TableCell>
                  <Link :href="show(log.id)" class="text-primary hover:underline">
                    Ver detalles
                  </Link>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="logs.links" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
          <Button as-child variant="outline" size="sm" :disabled="!logs.links[0]?.url">
            <Link v-if="logs.links[0]?.url" :href="logs.links[0].url!"> Anterior </Link>
          </Button>
          <Button
            as-child
            variant="outline"
            size="sm"
            :disabled="!logs.links[logs.links.length - 1]?.url"
          >
            <Link
              v-if="logs.links[logs.links.length - 1]?.url"
              :href="logs.links[logs.links.length - 1].url!"
            >
              Siguiente
            </Link>
          </Button>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-muted-foreground">
              Mostrando
              <span class="font-medium">{{ logs.from || 0 }}</span>
              a
              <span class="font-medium">{{ logs.to || 0 }}</span>
              de
              <span class="font-medium">{{ logs.total }}</span>
              resultados
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
              <Link
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                  'relative inline-flex items-center border px-4 py-2 text-sm font-medium',
                  link.active
                    ? 'z-10 border-primary bg-primary text-primary-foreground'
                    : 'border-input bg-background hover:bg-muted',
                  'first:rounded-l-md last:rounded-r-md',
                ]"
              >
                {{ link.label }}
              </Link>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
