import React, { useEffect } from 'react';
import { useStock } from '../context/StockContext';
import { useProducts } from '../context/ProductContext';
import { useWarehouses } from '../context/WarehouseContext';

const cardStyle: React.CSSProperties = {
  backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', padding: '24px',
};

const DashboardPage: React.FC = () => {
  const { stock, movements, dashboard, loading, fetchStock, fetchMovements, fetchDashboard } = useStock();
  const { products, fetchProducts } = useProducts();
  const { warehouses, fetchWarehouses } = useWarehouses();

  useEffect(() => {
    fetchDashboard();
    fetchStock();
    fetchMovements();
    fetchProducts();
    fetchWarehouses();
  }, [fetchDashboard, fetchStock, fetchMovements, fetchProducts, fetchWarehouses]);

  const totalQuantity = stock.reduce((sum, s) => sum + s.quantity, 0);
  const uniqueProducts = new Set(stock.map(s => s.productId)).size;
  const recentMovements = movements.slice(0, 10);

  const movementCounts: Record<string, number> = {};
  movements.forEach(m => { movementCounts[m.movementType] = (movementCounts[m.movementType] || 0) + 1; });

  const typeColors: Record<string, string> = {
    receive: '#7b1fa2', store: '#1565c0', pick: '#e65100',
    pack: '#2e7d32', ship: '#c62828', transfer: '#455a64',
  };

  return (
    <div>
      <h2 style={{ margin: '0 0 20px 0' }}>Dashboard</h2>

      {loading && <p>Loading...</p>}

      {/* Summary cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '16px', marginBottom: '24px' }}>
        <div style={{ ...cardStyle, borderLeft: '4px solid #1a237e' }}>
          <div style={{ fontSize: '13px', color: '#666', marginBottom: '4px' }}>Total Products</div>
          <div style={{ fontSize: '32px', fontWeight: 700, color: '#1a237e' }}>{products.length}</div>
        </div>
        <div style={{ ...cardStyle, borderLeft: '4px solid #1565c0' }}>
          <div style={{ fontSize: '13px', color: '#666', marginBottom: '4px' }}>Warehouses</div>
          <div style={{ fontSize: '32px', fontWeight: 700, color: '#1565c0' }}>{warehouses.length}</div>
        </div>
        <div style={{ ...cardStyle, borderLeft: '4px solid #2e7d32' }}>
          <div style={{ fontSize: '13px', color: '#666', marginBottom: '4px' }}>Total Stock Quantity</div>
          <div style={{ fontSize: '32px', fontWeight: 700, color: '#2e7d32' }}>{totalQuantity}</div>
        </div>
        <div style={{ ...cardStyle, borderLeft: '4px solid #e65100' }}>
          <div style={{ fontSize: '13px', color: '#666', marginBottom: '4px' }}>Products in Stock</div>
          <div style={{ fontSize: '32px', fontWeight: 700, color: '#e65100' }}>{uniqueProducts}</div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px' }}>
        {/* Movement breakdown */}
        <div style={cardStyle}>
          <h3 style={{ margin: '0 0 16px 0' }}>Movements by Type</h3>
          {Object.keys(movementCounts).length === 0 ? (
            <p style={{ color: '#999' }}>No movements yet</p>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
              {Object.entries(movementCounts).sort((a, b) => b[1] - a[1]).map(([type, count]) => {
                const maxCount = Math.max(...Object.values(movementCounts));
                return (
                  <div key={type}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                      <span style={{ fontSize: '14px', fontWeight: 600, textTransform: 'capitalize' }}>{type}</span>
                      <span style={{ fontSize: '14px', color: '#666' }}>{count}</span>
                    </div>
                    <div style={{ height: '8px', backgroundColor: '#f5f5f5', borderRadius: '4px', overflow: 'hidden' }}>
                      <div style={{
                        height: '100%', borderRadius: '4px',
                        width: `${(count / maxCount) * 100}%`,
                        backgroundColor: typeColors[type] || '#999',
                      }} />
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Stock by warehouse */}
        <div style={cardStyle}>
          <h3 style={{ margin: '0 0 16px 0' }}>Stock by Warehouse</h3>
          {warehouses.length === 0 ? (
            <p style={{ color: '#999' }}>No warehouses</p>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {warehouses.map(wh => {
                const whStock = stock.filter(s => s.warehouseId === wh.id);
                const whQty = whStock.reduce((sum, s) => sum + s.quantity, 0);
                const locCount = wh.locations?.length || 0;
                return (
                  <div key={wh.id} style={{ padding: '12px', backgroundColor: '#fafafa', borderRadius: '4px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                      <div>
                        <span style={{ fontWeight: 600 }}>{wh.name}</span>
                        <span style={{ marginLeft: '8px', fontSize: '12px', color: '#999', fontFamily: 'monospace' }}>{wh.code}</span>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div style={{ fontWeight: 700, color: '#1a237e' }}>{whQty} units</div>
                        <div style={{ fontSize: '12px', color: '#666' }}>{locCount} locations</div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* Recent movements */}
      <div style={{ ...cardStyle, marginTop: '24px' }}>
        <h3 style={{ margin: '0 0 16px 0' }}>Recent Movements</h3>
        {recentMovements.length === 0 ? (
          <p style={{ color: '#999', textAlign: 'center' }}>No recent movements</p>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ backgroundColor: '#fafafa' }}>
                {['Type', 'Product', 'Qty', 'Warehouse', 'Reference', 'Date'].map(h => (
                  <th key={h} style={{ padding: '8px 12px', textAlign: 'left', borderBottom: '2px solid #eee', fontSize: '12px' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {recentMovements.map((m, i) => (
                <tr key={i} style={{ borderBottom: '1px solid #f5f5f5' }}>
                  <td style={{ padding: '8px 12px' }}>
                    <span style={{
                      padding: '2px 8px', borderRadius: '10px', fontSize: '11px', fontWeight: 600,
                      color: typeColors[m.movementType] || '#333',
                      backgroundColor: `${typeColors[m.movementType]}15` || '#f5f5f5',
                    }}>{m.movementType}</span>
                  </td>
                  <td style={{ padding: '8px 12px', fontSize: '13px' }}>{m.productId}</td>
                  <td style={{ padding: '8px 12px', fontSize: '13px', fontWeight: 700 }}>{m.quantity}</td>
                  <td style={{ padding: '8px 12px', fontSize: '13px' }}>{m.warehouseId}</td>
                  <td style={{ padding: '8px 12px', fontSize: '12px', fontFamily: 'monospace' }}>{m.reference || '-'}</td>
                  <td style={{ padding: '8px 12px', fontSize: '12px', color: '#666' }}>{m.createdAt ? new Date(m.createdAt).toLocaleString() : '-'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Dashboard API data */}
      {dashboard && (
        <div style={{ ...cardStyle, marginTop: '24px' }}>
          <h3 style={{ margin: '0 0 16px 0' }}>API Dashboard Summary</h3>
          <pre style={{ backgroundColor: '#fafafa', padding: '16px', borderRadius: '4px', overflow: 'auto', fontSize: '13px' }}>
            {JSON.stringify(dashboard, null, 2)}
          </pre>
        </div>
      )}
    </div>
  );
};

export default DashboardPage;
