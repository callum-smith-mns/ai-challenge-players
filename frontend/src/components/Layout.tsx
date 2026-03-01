import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const navItems = [
  { path: '/', label: 'Dashboard' },
  { path: '/products', label: 'Products' },
  { path: '/warehouses', label: 'Warehouses' },
  { path: '/stock', label: 'Stock' },
  { path: '/movements', label: 'Movements' },
];

const Layout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const location = useLocation();

  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#f5f5f5' }}>
      <nav style={{
        backgroundColor: '#1a237e',
        color: 'white',
        padding: '0 24px',
        display: 'flex',
        alignItems: 'center',
        height: '56px',
        boxShadow: '0 2px 4px rgba(0,0,0,0.2)',
      }}>
        <h1 style={{ margin: 0, fontSize: '1.2rem', marginRight: '32px' }}>
          Warehouse Manager
        </h1>
        <div style={{ display: 'flex', gap: '4px' }}>
          {navItems.map(item => (
            <Link
              key={item.path}
              to={item.path}
              style={{
                color: 'white',
                textDecoration: 'none',
                padding: '8px 16px',
                borderRadius: '4px',
                backgroundColor: location.pathname === item.path ? 'rgba(255,255,255,0.2)' : 'transparent',
                fontSize: '0.9rem',
              }}
            >
              {item.label}
            </Link>
          ))}
        </div>
      </nav>
      <main style={{ padding: '24px', maxWidth: '1200px', margin: '0 auto' }}>
        {children}
      </main>
    </div>
  );
};

export default Layout;
