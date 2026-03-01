import React, { useEffect, useState } from 'react';
import { useStock } from '../context/StockContext';

const typeColors: Record<string, { bg: string; color: string }> = {
  receive: { bg: '#f3e5f5', color: '#7b1fa2' },
  store: { bg: '#e3f2fd', color: '#1565c0' },
  pick: { bg: '#fff3e0', color: '#e65100' },
  pack: { bg: '#e8f5e9', color: '#2e7d32' },
  ship: { bg: '#ffebee', color: '#c62828' },
  transfer: { bg: '#eceff1', color: '#455a64' },
};

const MovementsPage: React.FC = () => {
  const { movements, loading, error, fetchMovements } = useStock();
  const [typeFilter, setTypeFilter] = useState('');

  useEffect(() => { fetchMovements(); }, [fetchMovements]);

  const filtered = typeFilter ? movements.filter(m => m.movementType === typeFilter) : movements;

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
        <h2 style={{ margin: 0 }}>Stock Movements</h2>
        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
          <label style={{ fontSize: '13px', fontWeight: 600 }}>Filter by type:</label>
          <select style={{ padding: '6px 12px', borderRadius: '4px', border: '1px solid #ddd' }} value={typeFilter} onChange={e => setTypeFilter(e.target.value)}>
            <option value="">All</option>
            {['receive', 'store', 'pick', 'pack', 'ship', 'transfer'].map(t => (
              <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>
            ))}
          </select>
          <button onClick={() => fetchMovements()} style={{
            padding: '6px 16px', backgroundColor: '#1a237e', color: 'white',
            border: 'none', borderRadius: '4px', cursor: 'pointer', fontSize: '13px',
          }}>Refresh</button>
        </div>
      </div>

      {error && <div style={{ padding: '12px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '16px' }}>{error}</div>}

      <div style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', overflow: 'hidden' }}>
        <div style={{ padding: '16px', borderBottom: '1px solid #eee' }}>
          <span style={{ fontSize: '14px', color: '#666' }}>Showing {filtered.length} movements</span>
        </div>
        {loading ? <p style={{ padding: '16px' }}>Loading...</p> : filtered.length === 0 ? (
          <p style={{ padding: '24px', textAlign: 'center', color: '#999' }}>No movements found</p>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ backgroundColor: '#fafafa' }}>
                {['Type', 'Product', 'Quantity', 'Warehouse', 'From', 'To', 'Reference', 'Date'].map(h => (
                  <th key={h} style={{ padding: '10px 12px', textAlign: 'left', borderBottom: '2px solid #eee', fontSize: '13px' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map((m, i) => {
                const tc = typeColors[m.movementType] || { bg: '#f5f5f5', color: '#333' };
                return (
                  <tr key={i} style={{ borderBottom: '1px solid #f5f5f5' }}>
                    <td style={{ padding: '10px 12px' }}>
                      <span style={{
                        padding: '3px 10px', borderRadius: '12px', fontSize: '12px',
                        fontWeight: 600, backgroundColor: tc.bg, color: tc.color,
                      }}>{m.movementType}</span>
                    </td>
                    <td style={{ padding: '10px 12px', fontSize: '14px' }}>{m.productId}</td>
                    <td style={{ padding: '10px 12px', fontSize: '14px', fontWeight: 700 }}>{m.quantity}</td>
                    <td style={{ padding: '10px 12px', fontSize: '13px' }}>{m.warehouseId}</td>
                    <td style={{ padding: '10px 12px', fontSize: '13px' }}>{m.fromLocationId || '-'}</td>
                    <td style={{ padding: '10px 12px', fontSize: '13px' }}>{m.toLocationId || '-'}</td>
                    <td style={{ padding: '10px 12px', fontSize: '13px', fontFamily: 'monospace' }}>{m.reference || '-'}</td>
                    <td style={{ padding: '10px 12px', fontSize: '12px', color: '#666' }}>{m.createdAt ? new Date(m.createdAt).toLocaleString() : '-'}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default MovementsPage;
