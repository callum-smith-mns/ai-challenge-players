import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { Stock, StockMovement, DashboardSummary, stockApi } from '../api/client';

interface StockContextType {
  stock: Stock[];
  movements: StockMovement[];
  dashboard: DashboardSummary | null;
  loading: boolean;
  error: string | null;
  fetchStock: (filters?: Record<string, string>) => Promise<void>;
  fetchMovements: (filters?: Record<string, string>) => Promise<void>;
  fetchDashboard: () => Promise<void>;
  receiveStock: (data: Record<string, unknown>) => Promise<StockMovement>;
  moveStock: (data: Record<string, unknown>) => Promise<StockMovement>;
  storeStock: (data: Record<string, unknown>) => Promise<StockMovement>;
  pickStock: (data: Record<string, unknown>) => Promise<StockMovement>;
  packStock: (data: Record<string, unknown>) => Promise<StockMovement>;
  shipStock: (data: Record<string, unknown>) => Promise<StockMovement>;
}

const StockContext = createContext<StockContextType | undefined>(undefined);

export const StockProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [stock, setStock] = useState<Stock[]>([]);
  const [movements, setMovements] = useState<StockMovement[]>([]);
  const [dashboard, setDashboard] = useState<DashboardSummary | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchStock = useCallback(async (filters?: Record<string, string>) => {
    setLoading(true);
    setError(null);
    try {
      const response = await stockApi.list(filters);
      setStock(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to fetch stock');
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchMovements = useCallback(async (filters?: Record<string, string>) => {
    setLoading(true);
    setError(null);
    try {
      const response = await stockApi.movements(filters);
      setMovements(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to fetch movements');
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchDashboard = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await stockApi.dashboard();
      setDashboard(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to fetch dashboard');
    } finally {
      setLoading(false);
    }
  }, []);

  const performMovement = useCallback(async (
    action: (data: Record<string, unknown>) => Promise<any>,
    data: Record<string, unknown>
  ) => {
    const response = await action(data);
    if (response.data.success) {
      return response.data.data;
    }
    throw new Error('Movement failed');
  }, []);

  const receiveStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.receive, data);
  }, [performMovement]);

  const moveStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.move, data);
  }, [performMovement]);

  const storeStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.store, data);
  }, [performMovement]);

  const pickStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.pick, data);
  }, [performMovement]);

  const packStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.pack, data);
  }, [performMovement]);

  const shipStock = useCallback(async (data: Record<string, unknown>) => {
    return performMovement(stockApi.ship, data);
  }, [performMovement]);

  return (
    <StockContext.Provider value={{
      stock, movements, dashboard, loading, error,
      fetchStock, fetchMovements, fetchDashboard,
      receiveStock, moveStock, storeStock, pickStock, packStock, shipStock,
    }}>
      {children}
    </StockContext.Provider>
  );
};

export const useStock = (): StockContextType => {
  const context = useContext(StockContext);
  if (!context) {
    throw new Error('useStock must be used within a StockProvider');
  }
  return context;
};
