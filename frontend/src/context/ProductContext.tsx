import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { Product, productApi } from '../api/client';

interface ProductContextType {
  products: Product[];
  loading: boolean;
  error: string | null;
  fetchProducts: () => Promise<void>;
  createProduct: (data: Partial<Product>) => Promise<Product>;
  updateProduct: (id: string, data: Partial<Product>) => Promise<Product>;
  deleteProduct: (id: string) => Promise<void>;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export const ProductProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchProducts = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await productApi.list();
      setProducts(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to fetch products');
    } finally {
      setLoading(false);
    }
  }, []);

  const createProduct = useCallback(async (data: Partial<Product>) => {
    const response = await productApi.create(data);
    if (response.data.success) {
      setProducts(prev => [...prev, response.data.data]);
      return response.data.data;
    }
    throw new Error('Failed to create product');
  }, []);

  const updateProduct = useCallback(async (id: string, data: Partial<Product>) => {
    const response = await productApi.update(id, data);
    if (response.data.success) {
      setProducts(prev => prev.map(p => p.id === id ? response.data.data : p));
      return response.data.data;
    }
    throw new Error('Failed to update product');
  }, []);

  const deleteProduct = useCallback(async (id: string) => {
    await productApi.delete(id);
    setProducts(prev => prev.filter(p => p.id !== id));
  }, []);

  return (
    <ProductContext.Provider value={{
      products, loading, error,
      fetchProducts, createProduct, updateProduct, deleteProduct,
    }}>
      {children}
    </ProductContext.Provider>
  );
};

export const useProducts = (): ProductContextType => {
  const context = useContext(ProductContext);
  if (!context) {
    throw new Error('useProducts must be used within a ProductProvider');
  }
  return context;
};
