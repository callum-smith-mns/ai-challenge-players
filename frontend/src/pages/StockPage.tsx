import React, { useEffect, useState } from 'react';
import { useStock } from '../context/StockContext';
import { useProducts } from '../context/ProductContext';
import { useWarehouses } from '../context/WarehouseContext';
import { Location } from '../api/client';

type OperationTab = 'receive' | 'store' | 'pick' | 'pack' | 'ship' | 'move';

const tabConfig: { key: OperationTab; label: string; color: string }[] = [
  { key: 'receive', label: 'Receive', color: '#7b1fa2' },
  { key: 'store', label: 'Store', color: '#1565c0' },
  { key: 'pick', label: 'Pick', color: '#e65100' },
  { key: 'pack', label: 'Pack', color: '#2e7d32' },
  { key: 'ship', label: 'Ship', color: '#c62828' },
  { key: 'move', label: 'Transfer', color: '#455a64' },
];

const StockPage: React.FC = () => {
  const {
    stock, loading, error,
    fetchStock, receiveStock, storeStock, pickStock, packStock, shipStock, moveStock,
  } = useStock();
  const { products, fetchProducts } = useProducts();
  const { warehouses, fetchWarehouses } = useWarehouses();

  const [tab, setTab] = useState<OperationTab>('receive');
  const [opError, setOpError] = useState<string | null>(null);
  const [opSuccess, setOpSuccess] = useState<string | null>(null);

  const [productId, setProductId] = useState('');
  const [warehouseId, setWarehouseId] = useState('');
  const [locationId, setLocationId] = useState('');
  const [toWarehouseId, setToWarehouseId] = useState('');
  const [toLocationId, setToLocationId] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [reference, setReference] = useState('');
  const [notes, setNotes] = useState('');

  useEffect(() => { fetchStock(); fetchProducts(); fetchWarehouses(); }, [fetchStock, fetchProducts, fetchWarehouses]);

  const resetOp = () => {
    setProductId(''); setWarehouseId(''); setLocationId('');
    setToWarehouseId(''); setToLocationId('');
    setQuantity(1); setReference(''); setNotes('');
    setOpError(null);
  };

  const getLocations = (whId: string, filter?: Location['type'][]): Location[] => {
    const wh = warehouses.find(w => w.id === whId);
    if (!wh || !wh.locations) return [];
    return filter ? wh.locations.filter(l => filter.includes(l.type)) : wh.locations;
  };

  const performOperation = async () => {
    setOpError(null);
    setOpSuccess(null);
    try {
      const common = { productId, warehouseId, quantity, reference, notes };
      switch (tab) {
        case 'receive': await receiveStock({ ...common, locationId }); break;
        case 'store': await storeStock({ ...common, fromLocationId: locationId, toLocationId }); break;
        case 'pick': await pickStock({ ...common, fromLocationId: locationId, toLocationId }); break;
        case 'pack': await packStock({ ...common, fromLocationId: locationId, toLocationId }); break;
        case 'ship': await shipStock({ ...common, fromLocationId: locationId }); break;
        case 'move': await moveStock({ ...common, fromLocationId: locationId, toWarehouseId: toWarehouseId || undefined, toLocationId }); break;
      }
      setOpSuccess(`${tab.charAt(0).toUpperCase() + tab.slice(1)} operation completed`);
      resetOp();
      fetchStock();
    } catch (err: any) {
      setOpError(err.response?.data?.error || err.message || 'Operation failed');
    }
  };

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '8px', border: '1px solid #ddd',
    borderRadius: '4px', fontSize: '14px', boxSizing: 'border-box',
  };

  const labelStyle: React.CSSProperties = {
    display: 'block', marginBottom: '4px', fontWeight: '600', fontSize: '13px',
  };

  const needsToLocation = ['store', 'pick', 'pack', 'move'].includes(tab);
  const needsToWarehouse = tab === 'move';

  // Filter source locations by tab type
  const srcLocationFilter = (): Location['type'][] | undefined => {
    switch (tab) {
      case 'receive': return ['receiving'];
      case 'store': return ['receiving'];
      case 'pick': return ['storage'];
      case 'pack': return ['picking'];
      case 'ship': return ['picked'];
      default: return undefined;
    }
  };

  const dstLocationFilter = (): Location['type'][] | undefined => {
    switch (tab) {
      case 'store': return ['storage'];
      case 'pick': return ['picking'];
      case 'pack': return ['picked'];
      default: return undefined;
    }
  };

  const srcLocations = warehouseId ? getLocations(warehouseId, srcLocationFilter()) : [];
  const dstWarehouse = needsToWarehouse ? (toWarehouseId || warehouseId) : warehouseId;
  const dstLocations = dstWarehouse ? getLocations(dstWarehouse, dstLocationFilter()) : [];

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
        <h2 style={{ margin: 0 }}>Stock Management</h2>
      </div>

      {error && <div style={{ padding: '12px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '16px' }}>{error}</div>}

      {/* Operation panel */}
      <div style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', marginBottom: '24px', overflow: 'hidden' }}>
        <div style={{ display: 'flex', borderBottom: '2px solid #eee' }}>
          {tabConfig.map(t => (
            <button key={t.key} onClick={() => { setTab(t.key); setOpError(null); setOpSuccess(null); }} style={{
              flex: 1, padding: '12px', cursor: 'pointer', border: 'none',
              backgroundColor: tab === t.key ? t.color : 'white',
              color: tab === t.key ? 'white' : '#666',
              fontWeight: tab === t.key ? 700 : 400,
              fontSize: '14px',
            }}>{t.label}</button>
          ))}
        </div>

        <div style={{ padding: '20px' }}>
          {opError && <div style={{ padding: '8px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '12px' }}>{opError}</div>}
          {opSuccess && <div style={{ padding: '8px', backgroundColor: '#e8f5e9', color: '#2e7d32', borderRadius: '4px', marginBottom: '12px' }}>{opSuccess}</div>}

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '16px' }}>
            <div>
              <label style={labelStyle}>Product *</label>
              <select style={inputStyle} value={productId} onChange={e => setProductId(e.target.value)}>
                <option value="">Select product...</option>
                {products.map(p => <option key={p.id} value={p.id}>{p.name} ({p.upc})</option>)}
              </select>
            </div>
            <div>
              <label style={labelStyle}>{needsToWarehouse ? 'Source Warehouse *' : 'Warehouse *'}</label>
              <select style={inputStyle} value={warehouseId} onChange={e => { setWarehouseId(e.target.value); setLocationId(''); }}>
                <option value="">Select warehouse...</option>
                {warehouses.map(w => <option key={w.id} value={w.id}>{w.name} ({w.code})</option>)}
              </select>
            </div>
            <div>
              <label style={labelStyle}>{needsToLocation ? 'Source Location *' : 'Location *'}</label>
              <select style={inputStyle} value={locationId} onChange={e => setLocationId(e.target.value)}>
                <option value="">Select location...</option>
                {srcLocations.map(l => <option key={l.id} value={l.id}>{l.name} [{l.type}]</option>)}
              </select>
            </div>

            {needsToWarehouse && (
              <div>
                <label style={labelStyle}>Destination Warehouse</label>
                <select style={inputStyle} value={toWarehouseId} onChange={e => { setToWarehouseId(e.target.value); setToLocationId(''); }}>
                  <option value="">Same warehouse</option>
                  {warehouses.map(w => <option key={w.id} value={w.id}>{w.name} ({w.code})</option>)}
                </select>
              </div>
            )}

            {needsToLocation && (
              <div>
                <label style={labelStyle}>Destination Location *</label>
                <select style={inputStyle} value={toLocationId} onChange={e => setToLocationId(e.target.value)}>
                  <option value="">Select location...</option>
                  {dstLocations.map(l => <option key={l.id} value={l.id}>{l.name} [{l.type}]</option>)}
                </select>
              </div>
            )}

            <div>
              <label style={labelStyle}>Quantity *</label>
              <input style={inputStyle} type="number" min={1} value={quantity} onChange={e => setQuantity(parseInt(e.target.value) || 1)} />
            </div>
            <div>
              <label style={labelStyle}>Reference</label>
              <input style={inputStyle} value={reference} onChange={e => setReference(e.target.value)} placeholder="PO number, order ID, etc." />
            </div>
            <div>
              <label style={labelStyle}>Notes</label>
              <input style={inputStyle} value={notes} onChange={e => setNotes(e.target.value)} />
            </div>
          </div>

          <button onClick={performOperation} style={{
            marginTop: '16px', padding: '10px 24px', border: 'none', borderRadius: '4px', cursor: 'pointer',
            backgroundColor: tabConfig.find(t => t.key === tab)!.color, color: 'white', fontSize: '14px', fontWeight: 600,
          }}>Execute {tab.charAt(0).toUpperCase() + tab.slice(1)}</button>
        </div>
      </div>

      {/* Current stock table */}
      <div style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', overflow: 'hidden' }}>
        <div style={{ padding: '16px', borderBottom: '1px solid #eee' }}>
          <h3 style={{ margin: 0 }}>Current Stock ({stock.length})</h3>
        </div>
        {loading ? <p style={{ padding: '16px' }}>Loading...</p> : stock.length === 0 ? (
          <p style={{ padding: '16px', textAlign: 'center', color: '#999' }}>No stock records</p>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ backgroundColor: '#fafafa' }}>
                {['Product', 'Warehouse', 'Location', 'Quantity', 'Updated'].map(h => (
                  <th key={h} style={{ padding: '10px 16px', textAlign: 'left', borderBottom: '2px solid #eee', fontSize: '13px' }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {stock.map((s, i) => (
                <tr key={i} style={{ borderBottom: '1px solid #f5f5f5' }}>
                  <td style={{ padding: '10px 16px', fontSize: '14px' }}>{s.productId}</td>
                  <td style={{ padding: '10px 16px', fontSize: '14px' }}>{s.warehouseId}</td>
                  <td style={{ padding: '10px 16px', fontSize: '14px' }}>{s.locationId}</td>
                  <td style={{ padding: '10px 16px', fontSize: '14px', fontWeight: 700 }}>{s.quantity}</td>
                  <td style={{ padding: '10px 16px', fontSize: '13px', color: '#666' }}>{s.updatedAt ? new Date(s.updatedAt).toLocaleString() : '-'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
};

export default StockPage;
