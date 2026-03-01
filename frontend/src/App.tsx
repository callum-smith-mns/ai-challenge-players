import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ProductProvider } from './context/ProductContext';
import { WarehouseProvider } from './context/WarehouseContext';
import { StockProvider } from './context/StockContext';
import Layout from './components/Layout';
import DashboardPage from './pages/DashboardPage';
import ProductsPage from './pages/ProductsPage';
import WarehousesPage from './pages/WarehousesPage';
import StockPage from './pages/StockPage';
import MovementsPage from './pages/MovementsPage';

function App() {
  return (
    <BrowserRouter>
      <ProductProvider>
        <WarehouseProvider>
          <StockProvider>
            <Layout>
              <Routes>
                <Route path="/" element={<DashboardPage />} />
                <Route path="/products" element={<ProductsPage />} />
                <Route path="/warehouses" element={<WarehousesPage />} />
                <Route path="/stock" element={<StockPage />} />
                <Route path="/movements" element={<MovementsPage />} />
                <Route path="*" element={<Navigate to="/" replace />} />
              </Routes>
            </Layout>
          </StockProvider>
        </WarehouseProvider>
      </ProductProvider>
    </BrowserRouter>
  );
}

export default App;
