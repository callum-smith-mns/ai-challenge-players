import React, { useEffect, useState } from 'react';
import { useWarehouses } from '../context/WarehouseContext';
import { Warehouse, Location } from '../api/client';

const locationTypes = ['storage', 'picking', 'picked', 'receiving'] as const;

const typeColors: Record<string, { bg: string; color: string }> = {
  storage: { bg: '#e3f2fd', color: '#1565c0' },
  picking: { bg: '#fff3e0', color: '#e65100' },
  picked: { bg: '#e8f5e9', color: '#2e7d32' },
  receiving: { bg: '#f3e5f5', color: '#7b1fa2' },
};

const WarehousesPage: React.FC = () => {
  const {
    warehouses, loading, error,
    fetchWarehouses, createWarehouse, updateWarehouse, deleteWarehouse,
    addLocation, deleteLocation,
  } = useWarehouses();

  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [formData, setFormData] = useState<Partial<Warehouse>>({ name: '', code: '', isActive: true });
  const [formError, setFormError] = useState<string | null>(null);

  const [showLocForm, setShowLocForm] = useState<string | null>(null);
  const [locFormData, setLocFormData] = useState<Partial<Location>>({ name: '', type: 'storage', capacity: 0, isActive: true });
  const [locFormError, setLocFormError] = useState<string | null>(null);

  const [expandedWarehouse, setExpandedWarehouse] = useState<string | null>(null);

  useEffect(() => { fetchWarehouses(); }, [fetchWarehouses]);

  const resetForm = () => {
    setFormData({ name: '', code: '', isActive: true });
    setEditingId(null);
    setShowForm(false);
    setFormError(null);
  };

  const handleEdit = (wh: Warehouse) => {
    setFormData(wh);
    setEditingId(wh.id || null);
    setShowForm(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    try {
      if (editingId) {
        await updateWarehouse(editingId, formData);
      } else {
        await createWarehouse(formData);
      }
      resetForm();
    } catch (err: any) {
      setFormError(err.response?.data?.error || err.message || 'Failed to save warehouse');
    }
  };

  const handleAddLocation = async (warehouseId: string) => {
    setLocFormError(null);
    try {
      await addLocation(warehouseId, locFormData);
      setShowLocForm(null);
      setLocFormData({ name: '', type: 'storage', capacity: 0, isActive: true });
    } catch (err: any) {
      setLocFormError(err.response?.data?.error || err.message || 'Failed to add location');
    }
  };

  const handleDeleteWarehouse = async (id: string) => {
    if (window.confirm('Delete this warehouse?')) {
      try { await deleteWarehouse(id); } catch (err: any) { alert(err.message); }
    }
  };

  const handleDeleteLocation = async (warehouseId: string, locationId: string) => {
    if (window.confirm('Delete this location?')) {
      try { await deleteLocation(warehouseId, locationId); } catch (err: any) { alert(err.message); }
    }
  };

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '8px', border: '1px solid #ddd',
    borderRadius: '4px', fontSize: '14px', boxSizing: 'border-box',
  };

  const labelStyle: React.CSSProperties = {
    display: 'block', marginBottom: '4px', fontWeight: '600', fontSize: '13px',
  };

  return (
    <div>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
        <h2 style={{ margin: 0 }}>Warehouses</h2>
        <button onClick={() => { resetForm(); setShowForm(true); }} style={{
          backgroundColor: '#1a237e', color: 'white', border: 'none',
          padding: '10px 20px', borderRadius: '4px', cursor: 'pointer', fontSize: '14px',
        }}>+ Add Warehouse</button>
      </div>

      {error && <div style={{ padding: '12px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '16px' }}>{error}</div>}

      {showForm && (
        <div style={{ backgroundColor: 'white', padding: '24px', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', marginBottom: '24px' }}>
          <h3>{editingId ? 'Edit Warehouse' : 'New Warehouse'}</h3>
          {formError && <div style={{ padding: '8px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '12px' }}>{formError}</div>}
          <form onSubmit={handleSubmit}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '16px' }}>
              <div><label style={labelStyle}>Name *</label><input style={inputStyle} value={formData.name || ''} onChange={e => setFormData({...formData, name: e.target.value})} required /></div>
              <div><label style={labelStyle}>Code * (uppercase, e.g. WH-001)</label><input style={inputStyle} value={formData.code || ''} onChange={e => setFormData({...formData, code: e.target.value.toUpperCase()})} required /></div>
              <div><label style={labelStyle}>Address</label><input style={inputStyle} value={formData.address || ''} onChange={e => setFormData({...formData, address: e.target.value})} /></div>
              <div><label style={labelStyle}>City</label><input style={inputStyle} value={formData.city || ''} onChange={e => setFormData({...formData, city: e.target.value})} /></div>
              <div><label style={labelStyle}>State</label><input style={inputStyle} value={formData.state || ''} onChange={e => setFormData({...formData, state: e.target.value})} /></div>
              <div><label style={labelStyle}>Postal Code</label><input style={inputStyle} value={formData.postalCode || ''} onChange={e => setFormData({...formData, postalCode: e.target.value})} /></div>
              <div><label style={labelStyle}>Country</label><input style={inputStyle} value={formData.country || ''} onChange={e => setFormData({...formData, country: e.target.value})} /></div>
            </div>
            <div style={{ marginTop: '16px', display: 'flex', gap: '8px' }}>
              <button type="submit" style={{ backgroundColor: '#1a237e', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' }}>{editingId ? 'Update' : 'Create'}</button>
              <button type="button" onClick={resetForm} style={{ backgroundColor: '#757575', color: 'white', border: 'none', padding: '10px 20px', borderRadius: '4px', cursor: 'pointer' }}>Cancel</button>
            </div>
          </form>
        </div>
      )}

      {loading ? <p>Loading...</p> : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          {warehouses.length === 0 ? (
            <div style={{ backgroundColor: 'white', padding: '24px', borderRadius: '8px', textAlign: 'center', color: '#999' }}>No warehouses found</div>
          ) : warehouses.map(wh => (
            <div key={wh.id} style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', overflow: 'hidden' }}>
              <div style={{ padding: '16px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: expandedWarehouse === wh.id ? '1px solid #eee' : 'none' }}>
                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <h3 style={{ margin: 0 }}>{wh.name}</h3>
                    <span style={{ fontFamily: 'monospace', backgroundColor: '#f5f5f5', padding: '2px 8px', borderRadius: '4px', fontSize: '13px' }}>{wh.code}</span>
                    <span style={{
                      padding: '2px 8px', borderRadius: '12px', fontSize: '12px',
                      backgroundColor: wh.isActive ? '#e8f5e9' : '#ffebee',
                      color: wh.isActive ? '#2e7d32' : '#c62828',
                    }}>{wh.isActive ? 'Active' : 'Inactive'}</span>
                  </div>
                  <div style={{ fontSize: '13px', color: '#666', marginTop: '4px' }}>
                    {[wh.address, wh.city, wh.state, wh.country].filter(Boolean).join(', ') || 'No address'}
                    {' · '}{wh.locations?.length || 0} locations
                  </div>
                </div>
                <div style={{ display: 'flex', gap: '8px' }}>
                  <button onClick={() => setExpandedWarehouse(expandedWarehouse === wh.id ? null : wh.id!)} style={{ padding: '6px 12px', cursor: 'pointer', backgroundColor: '#f5f5f5', border: '1px solid #ddd', borderRadius: '4px' }}>
                    {expandedWarehouse === wh.id ? 'Collapse' : 'Locations'}
                  </button>
                  <button onClick={() => handleEdit(wh)} style={{ padding: '6px 12px', cursor: 'pointer', backgroundColor: '#e3f2fd', border: '1px solid #90caf9', borderRadius: '4px' }}>Edit</button>
                  <button onClick={() => handleDeleteWarehouse(wh.id!)} style={{ padding: '6px 12px', cursor: 'pointer', backgroundColor: '#ffebee', border: '1px solid #ef9a9a', borderRadius: '4px', color: '#c62828' }}>Delete</button>
                </div>
              </div>

              {expandedWarehouse === wh.id && (
                <div style={{ padding: '16px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '12px' }}>
                    <h4 style={{ margin: 0 }}>Locations</h4>
                    <button onClick={() => setShowLocForm(showLocForm === wh.id ? null : wh.id!)} style={{
                      backgroundColor: '#1a237e', color: 'white', border: 'none',
                      padding: '6px 16px', borderRadius: '4px', cursor: 'pointer', fontSize: '13px',
                    }}>+ Add Location</button>
                  </div>

                  {showLocForm === wh.id && (
                    <div style={{ backgroundColor: '#fafafa', padding: '16px', borderRadius: '4px', marginBottom: '12px' }}>
                      {locFormError && <div style={{ padding: '8px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '8px', fontSize: '13px' }}>{locFormError}</div>}
                      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: '12px' }}>
                        <div><label style={labelStyle}>Name *</label><input style={inputStyle} value={locFormData.name || ''} onChange={e => setLocFormData({...locFormData, name: e.target.value})} /></div>
                        <div>
                          <label style={labelStyle}>Type *</label>
                          <select style={inputStyle} value={locFormData.type || 'storage'} onChange={e => setLocFormData({...locFormData, type: e.target.value as Location['type']})}>
                            {locationTypes.map(t => <option key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</option>)}
                          </select>
                        </div>
                        <div><label style={labelStyle}>Aisle</label><input style={inputStyle} value={locFormData.aisle || ''} onChange={e => setLocFormData({...locFormData, aisle: e.target.value})} /></div>
                        <div><label style={labelStyle}>Rack</label><input style={inputStyle} value={locFormData.rack || ''} onChange={e => setLocFormData({...locFormData, rack: e.target.value})} /></div>
                        <div><label style={labelStyle}>Shelf</label><input style={inputStyle} value={locFormData.shelf || ''} onChange={e => setLocFormData({...locFormData, shelf: e.target.value})} /></div>
                        <div><label style={labelStyle}>Bin</label><input style={inputStyle} value={locFormData.bin || ''} onChange={e => setLocFormData({...locFormData, bin: e.target.value})} /></div>
                        <div><label style={labelStyle}>Capacity</label><input style={inputStyle} type="number" value={locFormData.capacity || 0} onChange={e => setLocFormData({...locFormData, capacity: parseInt(e.target.value)})} /></div>
                        <div style={{ display: 'flex', alignItems: 'flex-end', gap: '8px' }}>
                          <button onClick={() => handleAddLocation(wh.id!)} style={{ backgroundColor: '#1a237e', color: 'white', border: 'none', padding: '8px 16px', borderRadius: '4px', cursor: 'pointer' }}>Add</button>
                          <button onClick={() => setShowLocForm(null)} style={{ backgroundColor: '#757575', color: 'white', border: 'none', padding: '8px 16px', borderRadius: '4px', cursor: 'pointer' }}>Cancel</button>
                        </div>
                      </div>
                    </div>
                  )}

                  {(!wh.locations || wh.locations.length === 0) ? (
                    <p style={{ color: '#999', textAlign: 'center' }}>No locations</p>
                  ) : (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: '8px' }}>
                      {wh.locations.map(loc => (
                        <div key={loc.id} style={{ border: '1px solid #eee', borderRadius: '4px', padding: '12px' }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <strong>{loc.name}</strong>
                            <span style={{
                              padding: '2px 8px', borderRadius: '12px', fontSize: '11px',
                              backgroundColor: typeColors[loc.type]?.bg || '#f5f5f5',
                              color: typeColors[loc.type]?.color || '#333',
                            }}>{loc.type}</span>
                          </div>
                          <div style={{ fontSize: '12px', color: '#666', marginTop: '4px' }}>
                            {[loc.aisle && `Aisle: ${loc.aisle}`, loc.rack && `Rack: ${loc.rack}`, loc.shelf && `Shelf: ${loc.shelf}`, loc.bin && `Bin: ${loc.bin}`].filter(Boolean).join(' · ') || 'No position details'}
                          </div>
                          <div style={{ fontSize: '12px', color: '#666', marginTop: '2px' }}>Capacity: {loc.capacity}</div>
                          <button onClick={() => handleDeleteLocation(wh.id!, loc.id!)} style={{ marginTop: '8px', padding: '2px 8px', cursor: 'pointer', backgroundColor: '#ffebee', border: '1px solid #ef9a9a', borderRadius: '4px', color: '#c62828', fontSize: '11px' }}>Remove</button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default WarehousesPage;
