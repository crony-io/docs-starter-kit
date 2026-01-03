<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/activity-logs';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
  AppWindow,
  Building2,
  Calendar,
  Clock,
  Code,
  Compass,
  Cpu,
  FileText,
  Globe,
  Laptop,
  Link2,
  MapPin,
  Monitor,
  Network,
  Route,
  Server,
  Smartphone,
  User as UserIcon,
  Zap,
} from 'lucide-vue-next';
import { UAParser } from 'ua-parser-js';
import { computed } from 'vue';

interface IpInfo {
  ip?: string;
  city?: string;
  region?: string;
  country?: string;
  country_code?: string;
  postal?: string;
  latitude?: number;
  longitude?: number;
  timezone?: string;
  isp?: string;
  asn?: string;
  service?: string;
}

interface ActivityLog {
  id: number;
  user_id?: number;
  action: string;
  route_name?: string;
  url: string;
  method: string;
  ip_address: string;
  real_ip?: string;
  user_agent?: string;
  ip_info?: IpInfo;
  request_data?: Record<string, unknown>;
  response_data?: Record<string, unknown>;
  status_code?: number;
  execution_time?: number;
  controller?: string;
  controller_action?: string;
  description: string;
  metadata?: Record<string, unknown>;
  created_at: string;
  updated_at: string;
  user?: User;
}

interface Props {
  log: ActivityLog;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Logs de Actividad',
    href: index().url,
  },
];

const props = defineProps<Props>();

// Parse user agent
const parsedUA = computed(() => {
  if (!props.log.user_agent) {
    return null;
  }
  return UAParser(props.log.user_agent);
});

const browserInfo = computed(() => parsedUA.value?.browser);
const osInfo = computed(() => parsedUA.value?.os);
const deviceInfo = computed(() => parsedUA.value?.device);
const engineInfo = computed(() => parsedUA.value?.engine);
const cpuInfo = computed(() => parsedUA.value?.cpu);

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('es-ES');
};

const formatExecutionTime = (time?: number) => {
  if (!time) {
    return 'N/A';
  }
  return `${time}ms`;
};

const formatJson = (data: Record<string, unknown> | undefined) => {
  if (!data) {
    return 'N/A';
  }
  return JSON.stringify(data, null, 2);
};

const getStatusText = (statusCode?: number) => {
  if (!statusCode) {
    return 'Sin estado';
  }
  if (statusCode >= 200 && statusCode < 300) {
    return 'Éxito';
  }
  if (statusCode >= 300 && statusCode < 400) {
    return 'Redirección';
  }
  if (statusCode >= 400 && statusCode < 500) {
    return 'Error del cliente';
  }
  return 'Error del servidor';
};
</script>

<template>
  <Head :title="`Log de Actividad #${log.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto w-full space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <Heading :title="`Log de Actividad #${log.id}`" :description="log.description" />
        <Button as-child variant="outline" size="sm">
          <Link :href="index()"> Volver a la lista </Link>
        </Button>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Información Principal Card -->
        <Card class="justify-center py-8">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <UserIcon :size="20" class="text-muted-foreground" />
              Información Principal
            </CardTitle>
          </CardHeader>
          <CardContent class="">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <UserIcon :size="14" />
                  Usuario
                </span>
                <span class="text-sm font-medium">
                  {{ log.user?.name || 'Anónimo' }}
                  <span v-if="log.user?.email" class="text-muted-foreground">
                    ({{ log.user.email }})
                  </span>
                </span>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Zap :size="14" />
                  Acción
                </span>
                <span class="text-sm font-medium">{{ log.action }}</span>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Code :size="14" />
                  Método
                </span>
                <Badge :variant="log.method === 'GET' ? 'secondary' : 'default'">
                  {{ log.method }}
                </Badge>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <FileText :size="14" />
                  Estado
                </span>
                <div class="flex items-center gap-2">
                  <Badge
                    v-if="log.status_code"
                    :variant="
                      log.status_code >= 200 && log.status_code < 300 ? 'default' : 'destructive'
                    "
                  >
                    {{ log.status_code }}
                  </Badge>
                  <span v-else class="text-sm text-muted-foreground">N/A</span>
                  <span class="text-xs text-muted-foreground">
                    {{ getStatusText(log.status_code) }}
                  </span>
                </div>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Clock :size="14" />
                  Tiempo de ejecución
                </span>
                <span class="text-sm font-medium">
                  {{ formatExecutionTime(log.execution_time) }}
                </span>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Calendar :size="14" />
                  Fecha
                </span>
                <span class="text-sm font-medium">
                  {{ formatDate(log.created_at) }}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Información Técnica Card -->
        <Card class="justify-center py-8">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Server :size="20" class="text-muted-foreground" />
              Información Técnica
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-3">
              <div class="space-y-1">
                <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <Link2 :size="14" />
                  URL
                </span>
                <p class="font-mono text-sm break-all">
                  {{ log.url }}
                </p>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Route :size="14" />
                  Ruta
                </span>
                <code class="rounded bg-muted px-2 py-1 text-xs">
                  {{ log.route_name || 'N/A' }}
                </code>
              </div>
              <Separator />

              <div class="space-y-1">
                <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <Code :size="14" />
                  Controlador
                </span>
                <p class="font-mono text-sm">
                  {{ log.controller || 'N/A' }}
                  <span v-if="log.controller_action" class="text-muted-foreground">
                    ::{{ log.controller_action }}
                  </span>
                </p>
              </div>
              <Separator />

              <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                  <Network :size="14" />
                  IP Cliente
                </span>
                <code class="text-xs font-medium">{{ log.ip_address }}</code>
              </div>

              <div v-if="log.user_agent" class="pt-2">
                <Separator class="mb-3" />
                <div class="space-y-1">
                  <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Smartphone :size="14" />
                    User Agent
                  </span>
                  <p class="font-mono text-xs break-all text-muted-foreground">
                    {{ log.user_agent }}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- IP Information Card -->
      <Card v-if="log.real_ip || log.ip_info" class="justify-center py-8">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Globe :size="20" class="text-muted-foreground" />
            Información de IP
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-3">
            <div v-if="log.real_ip" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Network :size="14" />
                IP Pública Real
              </span>
              <code class="text-xs font-medium">{{ log.real_ip }}</code>
            </div>
            <Separator v-if="log.real_ip && log.ip_info?.country" />

            <div v-if="log.ip_info?.country" class="space-y-1">
              <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <MapPin :size="14" />
                Ubicación
              </span>
              <p class="text-sm font-medium">
                <span v-if="log.ip_info.city">{{ log.ip_info.city }}, </span>
                <span v-if="log.ip_info.region">{{ log.ip_info.region }}, </span>
                {{ log.ip_info.country }}
                <span v-if="log.ip_info.country_code" class="text-muted-foreground">
                  ({{ log.ip_info.country_code }})
                </span>
              </p>
            </div>

            <Separator v-if="log.ip_info?.timezone" />
            <div v-if="log.ip_info?.timezone" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Clock :size="14" />
                Zona Horaria
              </span>
              <span class="text-sm font-medium">{{ log.ip_info.timezone }}</span>
            </div>

            <Separator v-if="log.ip_info?.isp" />
            <div v-if="log.ip_info?.isp" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Building2 :size="14" />
                ISP/Proveedor
              </span>
              <span class="text-sm font-medium">{{ log.ip_info.isp }}</span>
            </div>

            <Separator v-if="log.ip_info?.latitude && log.ip_info?.longitude" />
            <div
              v-if="log.ip_info?.latitude && log.ip_info?.longitude"
              class="flex items-center justify-between"
            >
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Compass :size="14" />
                Coordenadas
              </span>
              <code class="text-xs">
                {{ log.ip_info.latitude }},
                {{ log.ip_info.longitude }}
              </code>
            </div>

            <div v-if="log.ip_info?.service" class="border-t pt-2">
              <span class="text-xs text-muted-foreground">
                Datos obtenidos de: {{ log.ip_info.service }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Browser & Device Information Card -->
      <Card v-if="parsedUA" class="justify-center py-8">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Monitor :size="20" class="text-muted-foreground" />
            Navegador y Dispositivo
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-3">
            <div v-if="browserInfo?.name" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <AppWindow :size="14" />
                Navegador
              </span>
              <span class="text-sm font-medium">
                {{ browserInfo.name }}
                <span v-if="browserInfo.version" class="text-muted-foreground">
                  v{{ browserInfo.version }}
                </span>
              </span>
            </div>
            <Separator v-if="browserInfo?.name && osInfo?.name" />

            <div v-if="osInfo?.name" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Laptop :size="14" />
                Sistema Operativo
              </span>
              <span class="text-sm font-medium">
                {{ osInfo.name }}
                <span v-if="osInfo.version" class="text-muted-foreground">
                  {{ osInfo.version }}
                </span>
              </span>
            </div>
            <Separator v-if="osInfo?.name && (deviceInfo?.type || deviceInfo?.vendor)" />

            <div v-if="deviceInfo?.type || deviceInfo?.vendor" class="space-y-1">
              <span class="text-xs text-muted-foreground">Dispositivo</span>
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">
                  <span v-if="deviceInfo.vendor">{{ deviceInfo.vendor }} </span>
                  <span v-if="deviceInfo.model">{{ deviceInfo.model }}</span>
                </span>
                <Badge v-if="deviceInfo.type" variant="secondary" class="text-xs">
                  {{ deviceInfo.type }}
                </Badge>
              </div>
            </div>

            <Separator v-if="engineInfo?.name" />
            <div v-if="engineInfo?.name" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Zap :size="14" />
                Motor de Renderizado
              </span>
              <span class="text-sm font-medium">
                {{ engineInfo.name }}
                <span v-if="engineInfo.version" class="text-muted-foreground">
                  v{{ engineInfo.version }}
                </span>
              </span>
            </div>

            <Separator v-if="cpuInfo?.architecture" />
            <div v-if="cpuInfo?.architecture" class="flex items-center justify-between">
              <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                <Cpu :size="14" />
                Arquitectura CPU
              </span>
              <code class="text-xs">{{ cpuInfo.architecture }}</code>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Request Data Card -->
      <Card v-if="log.request_data" class="justify-center py-8">
        <CardHeader>
          <CardTitle>Datos de la Petición</CardTitle>
        </CardHeader>
        <CardContent>
          <pre class="overflow-x-auto rounded-md bg-muted p-4 font-mono text-sm">{{
            formatJson(log.request_data)
          }}</pre>
        </CardContent>
      </Card>

      <!-- Response Data Card -->
      <Card v-if="log.response_data" class="justify-center py-8">
        <CardHeader>
          <CardTitle>Datos de la Respuesta</CardTitle>
        </CardHeader>
        <CardContent>
          <pre class="overflow-x-auto rounded-md bg-muted p-4 font-mono text-sm">{{
            formatJson(log.response_data)
          }}</pre>
        </CardContent>
      </Card>

      <!-- Metadata Card -->
      <Card v-if="log.metadata" class="justify-center py-8">
        <CardHeader>
          <CardTitle>Metadatos Adicionales</CardTitle>
        </CardHeader>
        <CardContent>
          <pre class="overflow-x-auto rounded-md bg-muted p-4 font-mono text-sm">{{
            formatJson(log.metadata)
          }}</pre>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
