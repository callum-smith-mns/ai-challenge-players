import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8080/api';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  error?: string;
}

export interface ListResponse<T> {
  success: boolean;
  data: T[];
  count: number;
}

// --- Product Types ---
export interface Product {
  id?: string;
  upc: string;
  ean?: string;
  name: string;
  description?: string;
  brand: string;
  category: string;
  weight: number;
  weightUnit: string;
  imageUrl?: string;
  ingredients: string[];
  allergens: string[];
  nutritionalInfo: Record<string, string | number>;
  storageInstructions?: string;
  shelfLifeDays: number;
  countryOfOrigin?: string;
  isActive: boolean;
  createdAt?: string;
  updatedAt?: string;
}

// --- Warehouse Types ---
export interface Location {
  id?: string;
  name: string;
  type: 'storage' | 'picking' | 'picked' | 'receiving';
  aisle?: string;
  rack?: string;
  shelf?: string;
  bin?: string;
  capacity: number;
  isActive: boolean;
  createdAt?: string;
}

export interface Warehouse {
  id?: string;
  name: string;
  code: string;
  address?: string;
  city?: string;
  state?: string;
  postalCode?: string;
  country?: string;
  isActive: boolean;
  locations: Location[];
  createdAt?: string;
  updatedAt?: string;
}

// --- Stock Types ---
export interface Stock {
  id?: string;
  productId: string;
  warehouseId: string;
  locationId: string;
  quantity: number;
  batchNumber?: string;
  expiryDate?: string;
  createdAt?: string;
  updatedAt?: string;
}

export interface StockMovement {
  id?: string;
  productId: string;
  warehouseId: string;
  fromLocationId?: string;
  toLocationId?: string;
  quantity: number;
  movementType: string;
  reference?: string;
  notes?: string;
  batchNumber?: string;
  createdAt?: string;
}

export interface DashboardSummary {
  totalStockItems: number;
  totalProducts: number;
  totalWarehouses: number;
  stockByWarehouse: Record<string, number>;
  stockByProduct: Record<string, number>;
  movementsByType: Record<string, number>;
  recentMovements: StockMovement[];
  totalMovements: number;
}

// --- Product API ---
export const productApi = {
  list: () => api.get<ListResponse<Product>>('/products'),
  get: (id: string) => api.get<ApiResponse<Product>>(`/products/${id}`),
  create: (data: Partial<Product>) => api.post<ApiResponse<Product>>('/products', data),
  update: (id: string, data: Partial<Product>) => api.put<ApiResponse<Product>>(`/products/${id}`, data),
  delete: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/products/${id}`),
};

// --- Warehouse API ---
export const warehouseApi = {
  list: () => api.get<ListResponse<Warehouse>>('/warehouses'),
  get: (id: string) => api.get<ApiResponse<Warehouse>>(`/warehouses/${id}`),
  create: (data: Partial<Warehouse>) => api.post<ApiResponse<Warehouse>>('/warehouses', data),
  update: (id: string, data: Partial<Warehouse>) => api.put<ApiResponse<Warehouse>>(`/warehouses/${id}`, data),
  delete: (id: string) => api.delete<ApiResponse<{ message: string }>>(`/warehouses/${id}`),
  addLocation: (warehouseId: string, data: Partial<Location>) =>
    api.post<ApiResponse<Location>>(`/warehouses/${warehouseId}/locations`, data),
  updateLocation: (warehouseId: string, locationId: string, data: Partial<Location>) =>
    api.put<ApiResponse<Location>>(`/warehouses/${warehouseId}/locations/${locationId}`, data),
  deleteLocation: (warehouseId: string, locationId: string) =>
    api.delete<ApiResponse<{ message: string }>>(`/warehouses/${warehouseId}/locations/${locationId}`),
};

// --- Stock API ---
export const stockApi = {
  list: (params?: Record<string, string>) => api.get<ListResponse<Stock>>('/stock', { params }),
  receive: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/receive', data),
  move: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/move', data),
  store: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/store', data),
  pick: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/pick', data),
  pack: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/pack', data),
  ship: (data: Record<string, unknown>) => api.post<ApiResponse<StockMovement>>('/stock/ship', data),
  movements: (params?: Record<string, string>) => api.get<ListResponse<StockMovement>>('/stock/movements', { params }),
  dashboard: () => api.get<ApiResponse<DashboardSummary>>('/stock/dashboard'),
};

export default api;
