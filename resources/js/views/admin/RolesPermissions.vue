<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-primary">Roles & Permissions</h1>
          <p class="text-secondary">Control who can see, change, approve, and manage each admin workflow.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-[minmax(220px,1fr)_auto] gap-2">
          <Input v-model="newRoleName" placeholder="New role name" />
          <Button icon="shield-plus-outline" :loading="savingRole" @click="createRole">Create Role</Button>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <Card :elevation="2" class="xl:col-span-4 p-0 overflow-hidden">
          <div class="p-4 border-b border-DEFAULT">
            <h2 class="text-lg font-semibold text-primary">Roles</h2>
            <p class="text-sm text-secondary">Select a role to review and update its access.</p>
          </div>
          <button
            v-for="role in roles"
            :key="role.id"
            type="button"
            class="w-full text-left px-4 py-3 border-b border-DEFAULT hover:bg-base transition-colors"
            :class="selectedRole?.id === role.id ? 'bg-primary/10' : ''"
            @click="selectedRoleId = role.id"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="font-semibold text-primary">{{ role.name }}</p>
                <p class="text-xs text-secondary">{{ role.permissions?.length || 0 }} permissions</p>
              </div>
              <span class="text-xs rounded-full bg-base px-2 py-1 text-secondary">{{ role.users_count || 0 }} users</span>
            </div>
          </button>
        </Card>

        <Card :elevation="2" class="xl:col-span-8 p-5">
          <div v-if="!selectedRole" class="text-secondary">Select a role to manage permissions.</div>
          <template v-else>
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-5">
              <div>
                <h2 class="text-xl font-semibold text-primary">{{ selectedRole.name }}</h2>
                <p class="text-sm text-secondary">Permissions are grouped by module for faster review.</p>
              </div>
              <Button icon="content-save-outline" :loading="savingPermissions" @click="savePermissions">Save Permissions</Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <section v-for="group in permissionGroups" :key="group.name" class="rounded-lg border border-DEFAULT p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                  <h3 class="font-semibold text-primary">{{ group.name }}</h3>
                  <button type="button" class="text-xs text-primary hover:underline" @click="toggleGroup(group.permissions)">
                    Toggle group
                  </button>
                </div>
                <div class="space-y-2">
                  <label v-for="permission in group.permissions" :key="permission.name" class="flex items-center gap-2 text-sm text-primary">
                    <input
                      v-model="selectedPermissions"
                      type="checkbox"
                      :value="permission.name"
                      class="rounded border-DEFAULT text-primary focus:ring-primary/40"
                    />
                    <span>{{ permission.name }}</span>
                  </label>
                </div>
              </section>
            </div>
          </template>
        </Card>
      </div>

      <Card :elevation="2" class="p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
          <div>
            <h2 class="text-lg font-semibold text-primary">User Role Assignment</h2>
            <p class="text-sm text-secondary">Update a user’s role without changing their account details.</p>
          </div>
          <div class="flex gap-2">
            <Input v-model="userSearch" placeholder="Search users" @keyup.enter="loadUsers" />
            <Button variant="outline" icon="magnify" @click="loadUsers">Search</Button>
          </div>
        </div>

        <div v-if="loadingUsers" class="text-secondary">Loading users...</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-base text-secondary">
              <tr>
                <th class="text-left p-3">User</th>
                <th class="text-left p-3">Email</th>
                <th class="text-left p-3">Roles</th>
                <th class="text-left p-3">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-t border-DEFAULT">
                <td class="p-3 font-medium text-primary">{{ user.name }}</td>
                <td class="p-3 text-secondary">{{ user.email }}</td>
                <td class="p-3 min-w-[260px]">
                  <Select
                    :model-value="roleDrafts[user.id] || user.roles?.[0]?.name || ''"
                    :options="roleOptions"
                    placeholder="Select role"
                    @update:model-value="(value) => roleDrafts[user.id] = value"
                  />
                </td>
                <td class="p-3">
                  <Button size="sm" :loading="savingUserId === user.id" @click="saveUserRole(user)">Save</Button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import AdminLayout from '../../components/admin/AdminLayout.vue';
import Card from '../../components/ui/Card.vue';
import Button from '../../components/ui/Button.vue';
import Input from '../../components/ui/Input.vue';
import Select from '../../components/ui/Select.vue';

const toast = inject('toast');

const roles = ref([]);
const permissions = ref([]);
const users = ref([]);
const selectedRoleId = ref(null);
const selectedPermissions = ref([]);
const newRoleName = ref('');
const userSearch = ref('');
const roleDrafts = ref({});
const loadingUsers = ref(false);
const savingRole = ref(false);
const savingPermissions = ref(false);
const savingUserId = ref(null);

const selectedRole = computed(() => roles.value.find((role) => role.id === selectedRoleId.value));

const roleOptions = computed(() => [
  { value: '', label: 'Select role' },
  ...roles.value.map((role) => ({ value: role.name, label: role.name })),
]);

const permissionGroups = computed(() => {
  const groups = {};
  permissions.value.forEach((permission) => {
    const key = permission.name.includes('.') ? permission.name.split('.')[0] : permission.name.split(' ').pop();
    const groupName = key.charAt(0).toUpperCase() + key.slice(1);
    groups[groupName] ||= [];
    groups[groupName].push(permission);
  });

  return Object.entries(groups)
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([name, groupPermissions]) => ({ name, permissions: groupPermissions }));
});

watch(selectedRole, (role) => {
  selectedPermissions.value = (role?.permissions || []).map((permission) => permission.name);
}, { immediate: true });

const loadRoles = async () => {
  const { data } = await axios.get('/api/admin/roles-permissions');
  roles.value = data.roles || [];
  permissions.value = data.permissions || [];
  selectedRoleId.value ||= roles.value[0]?.id || null;
};

const loadUsers = async () => {
  loadingUsers.value = true;
  try {
    const { data } = await axios.get('/api/admin/role-users', {
      params: { q: userSearch.value || undefined, per_page: 50 },
    });
    users.value = data.data || [];
  } finally {
    loadingUsers.value = false;
  }
};

const createRole = async () => {
  if (!newRoleName.value.trim()) {
    toast?.error('Enter a role name.');
    return;
  }

  savingRole.value = true;
  try {
    await axios.post('/api/admin/roles', { name: newRoleName.value.trim(), permissions: [] });
    toast?.success('Role created');
    newRoleName.value = '';
    await loadRoles();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to create role');
  } finally {
    savingRole.value = false;
  }
};

const toggleGroup = (groupPermissions) => {
  const names = groupPermissions.map((permission) => permission.name);
  const allSelected = names.every((name) => selectedPermissions.value.includes(name));
  selectedPermissions.value = allSelected
    ? selectedPermissions.value.filter((name) => !names.includes(name))
    : [...new Set([...selectedPermissions.value, ...names])];
};

const savePermissions = async () => {
  if (!selectedRole.value) return;

  savingPermissions.value = true;
  try {
    await axios.put(`/api/admin/roles/${selectedRole.value.id}/permissions`, {
      permissions: selectedPermissions.value,
    });
    toast?.success('Role permissions updated');
    await loadRoles();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update permissions');
  } finally {
    savingPermissions.value = false;
  }
};

const saveUserRole = async (user) => {
  const role = roleDrafts.value[user.id] || user.roles?.[0]?.name || '';
  savingUserId.value = user.id;
  try {
    await axios.put(`/api/admin/users/${user.id}/roles`, { roles: role ? [role] : [] });
    toast?.success('User role updated');
    await loadUsers();
  } catch (error) {
    toast?.error(error.response?.data?.message || 'Unable to update user role');
  } finally {
    savingUserId.value = null;
  }
};

onMounted(async () => {
  await Promise.all([loadRoles(), loadUsers()]);
});
</script>
