# Property Integration Guide

## Backend Structure Overview

We have three types of properties in our Laravel backend:
1. Residential Properties
2. Commercial Properties
3. Land Properties

Each type has its own specific fields but shares common base properties.

## API Integration Points

### API Configuration and Service Setup

First, let's set up a centralized API configuration:

```js
// src/config/api.config.js
export const API_CONFIG = {
  BASE_URL: '/api',
  ENDPOINTS: {
    PROPERTIES: '/properties',
    PROPERTY_DETAILS: (id) => `/properties/${id}`,
    AGENT_PROPERTIES: '/agent/properties'
  }
}
```

Then create an enhanced property service:

```js
// src/services/propertyService.js
import axios from 'axios'
import { API_CONFIG } from '@/config/api.config'
import { useAuthStore } from '@/stores/auth'

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add request interceptor to handle auth token
apiClient.interceptors.request.use(config => {
  const authStore = useAuthStore()
  
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

// Add response interceptor to handle common errors
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout() // Handle unauthorized access
    }
    return Promise.reject(error)
  }
)

export const propertyService = {
  async getProperties(filters = {}) {
    try {
      const response = await apiClient.get(API_CONFIG.ENDPOINTS.PROPERTIES, {
        params: filters
      })
      return response.data
    } catch (error) {
      console.error('Error fetching properties:', error)
      throw error
    }
  },

  async getPropertyById(id) {
    try {
      const response = await apiClient.get(API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id))
      return response.data
    } catch (error) {
      console.error(`Error fetching property ${id}:`, error)
      throw error
    }
  },

  async createProperty(propertyData) {
    try {
      const response = await apiClient.post(API_CONFIG.ENDPOINTS.PROPERTIES, propertyData)
      return response.data
    } catch (error) {
      console.error('Error creating property:', error)
      throw error
    }
  },

  async updateProperty(id, propertyData) {
    try {
      const response = await apiClient.put(
        API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id), 
        propertyData
      )
      return response.data
    } catch (error) {
      console.error(`Error updating property ${id}:`, error)
      throw error
    }
  },

  async deleteProperty(id) {
    try {
      await apiClient.delete(API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id))
    } catch (error) {
      console.error(`Error deleting property ${id}:`, error)
      throw error
    }
  }
}
```

## Improved API Service Setup

### API Service Configuration
```js
// src/config/api.config.js
export const API_CONFIG = {
  BASE_URL: '/api',
  ENDPOINTS: {
    PROPERTIES: '/properties',
    PROPERTY_DETAILS: (id) => `/properties/${id}`,
    AGENT_PROPERTIES: '/agent/properties'
  }
}
```

### Enhanced Property Service
```js
// src/services/propertyService.js
import axios from 'axios'
import { API_CONFIG } from '@/config/api.config'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add request interceptor to handle auth token
apiClient.interceptors.request.use(config => {
  const authStore = useAuthStore()
  const { token } = storeToRefs(authStore)
  
  if (token.value) {
    config.headers.Authorization = `Bearer ${token.value}`
  }
  return config
})

// Add response interceptor to handle common errors
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout() // Handle unauthorized access
    }
    return Promise.reject(error)
  }
)

export const propertyService = {
  async getProperties(filters = {}) {
    try {
      const response = await apiClient.get(API_CONFIG.ENDPOINTS.PROPERTIES, {
        params: filters
      })
      return response.data
    } catch (error) {
      console.error('Error fetching properties:', error)
      throw error
    }
  },

  async getPropertyById(id) {
    try {
      const response = await apiClient.get(API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id))
      return response.data
    } catch (error) {
      console.error(`Error fetching property ${id}:`, error)
      throw error
    }
  },

  async createProperty(propertyData) {
    try {
      const response = await apiClient.post(API_CONFIG.ENDPOINTS.PROPERTIES, propertyData)
      return response.data
    } catch (error) {
      console.error('Error creating property:', error)
      throw error
    }
  },

  async updateProperty(id, propertyData) {
    try {
      const response = await apiClient.put(
        API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id), 
        propertyData
      )
      return response.data
    } catch (error) {
      console.error(`Error updating property ${id}:`, error)
      throw error
    }
  },

  async deleteProperty(id) {
    try {
      await apiClient.delete(API_CONFIG.ENDPOINTS.PROPERTY_DETAILS(id))
    } catch (error) {
      console.error(`Error deleting property ${id}:`, error)
      throw error
    }
  }
}
```

### Using the Enhanced Service with Pinia Store
```js
// src/stores/property.js
import { defineStore } from 'pinia'
import { propertyService } from '@/services/propertyService'

export const usePropertyStore = defineStore('property', {
  state: () => ({
    properties: [],
    currentProperty: null,
    loading: false,
    error: null
  }),

  getters: {
    getPropertyById: (state) => (id) => {
      return state.properties.find(p => p.id === id)
    },
    filteredProperties: (state) => (filters) => {
      return state.properties.filter(p => {
        // Add your filter logic here
        if (filters.type && p.property_type !== filters.type) return false
        if (filters.status && p.status !== filters.status) return false
        return true
      })
    }
  },

  actions: {
    async fetchProperties(filters = {}) {
      this.loading = true
      this.error = null
      try {
        const data = await propertyService.getProperties(filters)
        this.properties = data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchPropertyById(id) {
      this.loading = true
      this.error = null
      try {
        const data = await propertyService.getPropertyById(id)
        this.currentProperty = data
        return data
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
```

### Using in Components
```vue
<!-- src/views/properties/PropertyList.vue -->
<template>
  <div class="property-list">
    <div v-if="loading" class="loading">Loading...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else>
      <property-filters v-model="filters" @change="handleFiltersChange" />
      <property-grid :properties="filteredProperties" />
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { usePropertyStore } from '@/stores/property'
import PropertyFilters from '@/components/PropertyFilters.vue'
import PropertyGrid from '@/components/PropertyGrid.vue'

export default {
  name: 'PropertyList',
  components: {
    PropertyFilters,
    PropertyGrid
  },
  setup() {
    const propertyStore = usePropertyStore()
    const { properties, loading, error } = storeToRefs(propertyStore)
    const filters = ref({})

    const handleFiltersChange = async (newFilters) => {
      filters.value = newFilters
      await propertyStore.fetchProperties(newFilters)
    }

    onMounted(async () => {
      await propertyStore.fetchProperties()
    })

    return {
      properties,
      loading,
      error,
      filters,
      handleFiltersChange
    }
  }
}
</script>
```

This improved setup provides:

1. Centralized API configuration
2. Axios instance with interceptors for:
   - Automatic token handling
   - Unauthorized access handling
   - Error handling
3. Pinia store integration for:
   - State management
   - Caching
   - Loading states
   - Error handling
4. Reusable service methods
5. Type-safe API endpoints
6. Better error handling and logging

## Store Setup

### Basic Property Store
```js
// src/stores/property.js
import { defineStore } from 'pinia'
import { propertyService } from '@/services/propertyService'

export const usePropertyStore = defineStore('property', {
  state: () => ({
    properties: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchProperties(filters = {}) {
      this.loading = true
      try {
        const data = await propertyService.getProperties(filters)
        this.properties = data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    }
  }
})
```

### Using the Store in Components
```vue
<template>
  <div>
    <div v-if="loading">Loading...</div>
    <div v-else>
      <div v-for="property in properties" :key="property.id">
        {{ property.title }}
      </div>
    </div>
  </div>
</template>

<script>
import { usePropertyStore } from '@/stores/property'
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'

export default {
  setup() {
    const propertyStore = usePropertyStore()
    // Use storeToRefs to maintain reactivity
    const { properties, loading } = storeToRefs(propertyStore)

    // Load properties when component mounts
    onMounted(() => {
      propertyStore.fetchProperties()
    })

    return {
      properties,
      loading
    }
  }
}
</script>
```

## Integration with Your Role System

### Admin View Component
```vue
<!-- src/views/admin/PropertiesView.vue -->
<template>
  <div>
    <h2>Property Management</h2>
    <property-list :properties="properties" />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { propertyService } from '@/services/propertyService'
import PropertyList from '@/components/PropertyList.vue'

export default {
  name: 'PropertiesView',
  components: {
    PropertyList
  },
  setup() {
    const properties = ref([])

    const loadProperties = async () => {
      try {
        properties.value = await propertyService.getProperties()
      } catch (error) {
        // Use your existing error handling
      }
    }

    onMounted(loadProperties)

    return {
      properties
    }
  }
}
</script>
```

### Agent Dashboard Component
```vue
<!-- src/views/agent/AgentProperties.vue -->
<template>
  <div>
    <h2>My Properties</h2>
    <property-list 
      :properties="myProperties" 
      @delete="handleDelete"
      @edit="handleEdit"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useStore } from 'vuex' // Or your existing store solution
import PropertyList from '@/components/PropertyList.vue'

export default {
  name: 'AgentProperties',
  components: {
    PropertyList
  },
  setup() {
    const store = useStore()
    const myProperties = ref([])

    const loadMyProperties = async () => {
      try {
        const userId = store.state.auth.user.id
        myProperties.value = await propertyService.getProperties({ agent_id: userId })
      } catch (error) {
        // Use your existing error handling
      }
    }

    onMounted(loadMyProperties)

    return {
      myProperties
    }
  }
}
</script>
```

### Client Property Browsing Component
```vue
<!-- src/views/properties/PropertyBrowser.vue -->
<template>
  <div>
    <div class="filters">
      <select v-model="selectedType">
        <option value="">All Types</option>
        <option value="residential">Residential</option>
        <option value="commercial">Commercial</option>
        <option value="land">Land</option>
      </select>
      <!-- Add your other filter inputs -->
    </div>

    <property-grid :properties="filteredProperties" />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import PropertyGrid from '@/components/PropertyGrid.vue'

export default {
  name: 'PropertyBrowser',
  components: {
    PropertyGrid
  },
  setup() {
    const properties = ref([])
    const selectedType = ref('')

    const filteredProperties = computed(() => {
      if (!selectedType.value) return properties.value
      return properties.value.filter(p => p.property_type === selectedType.value)
    })

    const loadProperties = async () => {
      try {
        properties.value = await propertyService.getProperties({ status: 'active' })
      } catch (error) {
        // Use your existing error handling
      }
    }

    onMounted(loadProperties)

    return {
      properties,
      selectedType,
      filteredProperties
    }
  }
}
</script>
```

## Property Components

### Property List Component
```vue
<!-- src/components/PropertyList.vue -->
<template>
  <div class="property-list">
    <div v-for="property in properties" :key="property.id" class="property-item">
      <h3>{{ property.title }}</h3>
      <p>{{ property.description }}</p>
      <property-details :property="property" />
      
      <!-- Role-based actions -->
      <div v-if="userRole === 'agent' && property.agent_id === currentUserId" class="actions">
        <button @click="$emit('edit', property)">Edit</button>
        <button @click="$emit('delete', property.id)">Delete</button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useStore } from 'vuex' // Or your existing store
import PropertyDetails from './PropertyDetails.vue'

export default {
  name: 'PropertyList',
  components: {
    PropertyDetails
  },
  props: {
    properties: {
      type: Array,
      required: true
    }
  },
  setup() {
    const store = useStore()
    
    const userRole = computed(() => store.state.auth.user?.role)
    const currentUserId = computed(() => store.state.auth.user?.id)

    return {
      userRole,
      currentUserId
    }
  }
}
</script>
```

### Property Type-Specific Components
```vue
<!-- src/components/property-types/ResidentialDetails.vue -->
<template>
  <div class="residential-details">
    <div class="specs">
      <span>{{ property.bedrooms }} Bedrooms</span>
      <span>{{ property.bathrooms }} Bathrooms</span>
      <span>{{ property.floor_area }} sq ft</span>
    </div>
    <div class="amenities" v-if="property.amenities">
      <!-- Your amenities list -->
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResidentialDetails',
  props: {
    property: {
      type: Object,
      required: true
    }
  }
}
</script>
```

## Error Handling Integration

Use your existing error handling system with the property service:

```js
// In your components
try {
  await propertyService.getProperties(filters)
} catch (error) {
  // Use your existing error notification system
  this.$notify({ // Or however you show errors
    type: 'error',
    message: error.response?.data?.message || 'Failed to load properties'
  })
}
```

## Property Form Components

### Base Property Form
```vue
<!-- src/components/forms/PropertyForm.vue -->
<template>
  <form @submit.prevent="handleSubmit">
    <div class="form-group">
      <label>Title</label>
      <input v-model="form.title" type="text" required />
    </div>
    
    <div class="form-group">
      <label>Property Type</label>
      <select v-model="form.property_type" required>
        <option value="residential">Residential</option>
        <option value="commercial">Commercial</option>
        <option value="land">Land</option>
      </select>
    </div>

    <div class="form-group">
      <label>Price</label>
      <input v-model.number="form.price" type="number" required />
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea v-model="form.description" required></textarea>
    </div>

    <!-- Address Fields -->
    <div class="form-group">
      <label>Address</label>
      <input v-model="form.address_line1" type="text" required />
    </div>
    
    <div class="form-row">
      <div class="form-group">
        <label>City</label>
        <input v-model="form.city" type="text" required />
      </div>
      <div class="form-group">
        <label>State</label>
        <input v-model="form.state" type="text" required />
      </div>
      <div class="form-group">
        <label>Postal Code</label>
        <input v-model="form.postal_code" type="text" required />
      </div>
    </div>

    <!-- Dynamic Property Type Fields -->
    <component 
      :is="propertyTypeForm" 
      v-model="form.type_specific_data"
      v-if="form.property_type"
    />

    <!-- Media Upload -->
    <div class="form-group">
      <label>Property Images</label>
      <input type="file" multiple @change="handleImageUpload" accept="image/*" />
    </div>

    <button type="submit" :disabled="isSubmitting">
      {{ isSubmitting ? 'Saving...' : 'Save Property' }}
    </button>
  </form>
</template>

<script>
import { ref, computed } from 'vue'
import ResidentialPropertyForm from './ResidentialPropertyForm.vue'
import CommercialPropertyForm from './CommercialPropertyForm.vue'
import LandPropertyForm from './LandPropertyForm.vue'

export default {
  name: 'PropertyForm',
  props: {
    initialData: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props, { emit }) {
    const form = ref({
      title: '',
      property_type: '',
      price: null,
      description: '',
      address_line1: '',
      city: '',
      state: '',
      postal_code: '',
      type_specific_data: {},
      ...props.initialData
    })

    const isSubmitting = ref(false)

    const propertyTypeForm = computed(() => {
      switch (form.value.property_type) {
        case 'residential':
          return ResidentialPropertyForm
        case 'commercial':
          return CommercialPropertyForm
        case 'land':
          return LandPropertyForm
        default:
          return null
      }
    })

    const handleImageUpload = (event) => {
      // Handle image upload logic
      const files = event.target.files
      // Add to form data or upload immediately based on your needs
    }

    const handleSubmit = async () => {
      try {
        isSubmitting.value = true
        // Emit form data for parent to handle
        emit('submit', form.value)
      } catch (error) {
        // Handle error
      } finally {
        isSubmitting.value = false
      }
    }

    return {
      form,
      isSubmitting,
      propertyTypeForm,
      handleImageUpload,
      handleSubmit
    }
  }
}
</script>
```

### Property Type-Specific Form Example
```vue
<!-- src/components/forms/ResidentialPropertyForm.vue -->
<template>
  <div class="residential-form">
    <div class="form-group">
      <label>Residential Type</label>
      <select v-model="typeData.residential_type" required>
        <option value="single_family">Single Family</option>
        <option value="apartment">Apartment</option>
        <option value="townhouse">Townhouse</option>
        <option value="duplex">Duplex</option>
        <option value="condo">Condo</option>
        <option value="villa">Villa</option>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Bedrooms</label>
        <input v-model.number="typeData.bedrooms" type="number" required />
      </div>
      <div class="form-group">
        <label>Bathrooms</label>
        <input v-model.number="typeData.bathrooms" type="number" required />
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Total Rooms</label>
        <input v-model.number="typeData.total_rooms" type="number" required />
      </div>
      <div class="form-group">
        <label>Floor Area (sq ft)</label>
        <input v-model.number="typeData.floor_area" type="number" required />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'ResidentialPropertyForm',
  props: {
    modelValue: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const typeData = ref({
      residential_type: '',
      bedrooms: null,
      bathrooms: null,
      total_rooms: null,
      floor_area: null,
      ...props.modelValue
    })

    watch(typeData, (newValue) => {
      emit('update:modelValue', newValue)
    }, { deep: true })

    return {
      typeData
    }
  }
}
</script>
```

### Using the Property Form
```vue
<!-- src/views/agent/CreateProperty.vue -->
<template>
  <div class="create-property">
    <h2>Create New Property</h2>
    <property-form @submit="handleCreateProperty" />
  </div>
</template>

<script>
import { ref } from 'vue'
import PropertyForm from '@/components/forms/PropertyForm.vue'
import { propertyService } from '@/services/propertyService'
import { useRouter } from 'vue-router'

export default {
  name: 'CreateProperty',
  components: {
    PropertyForm
  },
  setup() {
    const router = useRouter()

    const handleCreateProperty = async (formData) => {
      try {
        await propertyService.createProperty(formData)
        router.push('/agent/properties')
      } catch (error) {
        // Handle error with your notification system
      }
    }

    return {
      handleCreateProperty
    }
  }
}
</script>
```

## Next Steps

1. Add the property service to your services directory
2. Create the necessary Vue components
3. Add property routes to your router
4. Integrate with your Vuex store (if using Vuex)
5. Test the integration with your role system

## Route Integration Example

```js
// src/router/index.js
import { createRouter } from 'vue-router'

const routes = [
  {
    path: '/properties',
    component: () => import('@/views/properties/PropertyBrowser.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/agent/properties',
    component: () => import('@/views/agent/AgentProperties.vue'),
    meta: { requiresAuth: true, roles: ['agent'] }
  },
  {
    path: '/admin/properties',
    component: () => import('@/views/admin/PropertiesView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] }
  }
]

// Use with your existing router setup and navigation guards
```
