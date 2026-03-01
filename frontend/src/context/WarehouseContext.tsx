import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { Warehouse, Location, warehouseApi } from '../api/client';

interface WarehouseContextType {
  warehouses: Warehouse[];
  loading: boolean;
  error: string | null;
  fetchWarehouses: () => Promise<void>;
  createWarehouse: (data: Partial<Warehouse>) => Promise<Warehouse>;
  updateWarehouse: (id: string, data: Partial<Warehouse>) => Promise<Warehouse>;
  deleteWarehouse: (id: string) => Promise<void>;
  addLocation: (warehouseId: string, data: Partial<Location>) => Promise<Location>;
  updateLocation: (warehouseId: string, locationId: string, data: Partial<Location>) => Promise<Location>;
  deleteLocation: (warehouseId: string, locationId: string) => Promise<void>;
}

const WarehouseContext = createContext<WarehouseContextType | undefined>(undefined);

export const WarehouseProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [warehouses, setWarehouses] = useState<Warehouse[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchWarehouses = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await warehouseApi.list();
      setWarehouses(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to fetch warehouses');
    } finally {
      setLoading(false);
    }
  }, []);

  const createWarehouse = useCallback(async (data: Partial<Warehouse>) => {
    const response = await warehouseApi.create(data);
    if (response.data.success) {
      setWarehouses(prev => [...prev, response.data.data]);
      return response.data.data;
    }
    throw new Error('Failed to create warehouse');
  }, []);

  const updateWarehouse = useCallback(async (id: string, data: Partial<Warehouse>) => {
    const response = await warehouseApi.update(id, data);
    if (response.data.success) {
      setWarehouses(prev => prev.map(w => w.id === id ? response.data.data : w));
      return response.data.data;
    }
    throw new Error('Failed to update warehouse');
  }, []);

  const deleteWarehouse = useCallback(async (id: string) => {
    await warehouseApi.delete(id);
    setWarehouses(prev => prev.filter(w => w.id !== id));
  }, []);

  const addLocation = useCallback(async (warehouseId: string, data: Partial<Location>) => {
    const response = await warehouseApi.addLocation(warehouseId, data);
    if (response.data.success) {
      // Refresh warehouse to get updated locations
      const whResponse = await warehouseApi.get(warehouseId);
      if (whResponse.data.success) {
        setWarehouses(prev => prev.map(w => w.id === warehouseId ? whResponse.data.data : w));
      }
      return response.data.data;
    }
    throw new Error('Failed to add location');
  }, []);

  const updateLocation = useCallback(async (warehouseId: string, locationId: string, data: Partial<Location>) => {
    const response = await warehouseApi.updateLocation(warehouseId, locationId, data);
    if (response.data.success) {
      const whResponse = await warehouseApi.get(warehouseId);
      if (whResponse.data.success) {
        setWarehouses(prev => prev.map(w => w.id === warehouseId ? whResponse.data.data : w));
      }
      return response.data.data;
    }
    throw new Error('Failed to update location');
  }, []);

  const deleteLocation = useCallback(async (warehouseId: string, locationId: string) => {
    await warehouseApi.deleteLocation(warehouseId, locationId);
    const whResponse = await warehouseApi.get(warehouseId);
    if (whResponse.data.success) {
      setWarehouses(prev => prev.map(w => w.id === warehouseId ? whResponse.data.data : w));
    }
  }, []);

  return (
    <WarehouseContext.Provider value={{
      warehouses, loading, error,
      fetchWarehouses, createWarehouse, updateWarehouse, deleteWarehouse,
      addLocation, updateLocation, deleteLocation,
    }}>
      {children}
    </WarehouseContext.Provider>
  );
};

export const useWarehouses = (): WarehouseContextType => {
  const context = useContext(WarehouseContext);
  if (!context) {
    throw new Error('useWarehouses must be used within a WarehouseProvider');
  }
  return context;
};
