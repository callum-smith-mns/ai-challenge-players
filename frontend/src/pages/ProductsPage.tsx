import React, { useEffect, useState } from 'react';
import { useProducts } from '../context/ProductContext';
import { Product } from '../api/client';

const emptyProduct: Partial<Product> = {
  upc: '', ean: '', name: '', description: '', brand: '', category: '',
  weight: 0, weightUnit: 'g', imageUrl: '', ingredients: [], allergens: [],
  nutritionalInfo: {}, storageInstructions: '', shelfLifeDays: 0,
  countryOfOrigin: '', isActive: true,
};

const ProductsPage: React.FC = () => {
  const { products, loading, error, fetchProducts, createProduct, updateProduct, deleteProduct } = useProducts();
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [formData, setFormData] = useState<Partial<Product>>(emptyProduct);
  const [formError, setFormError] = useState<string | null>(null);
  const [ingredientInput, setIngredientInput] = useState('');
  const [allergenInput, setAllergenInput] = useState('');

  useEffect(() => { fetchProducts(); }, [fetchProducts]);

  const resetForm = () => {
    setFormData(emptyProduct);
    setEditingId(null);
    setShowForm(false);
    setFormError(null);
    setIngredientInput('');
    setAllergenInput('');
  };

  const handleEdit = (product: Product) => {
    setFormData(product);
    setEditingId(product.id || null);
    setShowForm(true);
    setIngredientInput(product.ingredients?.join(', ') || '');
    setAllergenInput(product.allergens?.join(', ') || '');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError(null);
    try {
      const data = {
        ...formData,
        ingredients: ingredientInput.split(',').map(s => s.trim()).filter(Boolean),
        allergens: allergenInput.split(',').map(s => s.trim()).filter(Boolean),
      };
      if (editingId) {
        await updateProduct(editingId, data);
      } else {
        await createProduct(data);
      }
      resetForm();
    } catch (err: any) {
      setFormError(err.response?.data?.error || err.message || 'Failed to save product');
    }
  };

  const handleDelete = async (id: string) => {
    if (window.confirm('Are you sure you want to delete this product?')) {
      try {
        await deleteProduct(id);
      } catch (err: any) {
        alert(err.response?.data?.error || 'Failed to delete');
      }
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
        <h2 style={{ margin: 0 }}>Products</h2>
        <button
          onClick={() => { resetForm(); setShowForm(true); }}
          style={{
            backgroundColor: '#1a237e', color: 'white', border: 'none',
            padding: '10px 20px', borderRadius: '4px', cursor: 'pointer', fontSize: '14px',
          }}
        >
          + Add Product
        </button>
      </div>

      {error && <div style={{ padding: '12px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '16px' }}>{error}</div>}

      {showForm && (
        <div style={{ backgroundColor: 'white', padding: '24px', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', marginBottom: '24px' }}>
          <h3>{editingId ? 'Edit Product' : 'New Product'}</h3>
          {formError && <div style={{ padding: '8px', backgroundColor: '#ffebee', color: '#c62828', borderRadius: '4px', marginBottom: '12px' }}>{formError}</div>}
          <form onSubmit={handleSubmit}>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '16px' }}>
              <div>
                <label style={labelStyle}>UPC (12 digits) *</label>
                <input style={inputStyle} value={formData.upc || ''} onChange={e => setFormData({...formData, upc: e.target.value})} required maxLength={12} />
              </div>
              <div>
                <label style={labelStyle}>EAN (13 digits)</label>
                <input style={inputStyle} value={formData.ean || ''} onChange={e => setFormData({...formData, ean: e.target.value})} maxLength={13} />
              </div>
              <div>
                <label style={labelStyle}>Name *</label>
                <input style={inputStyle} value={formData.name || ''} onChange={e => setFormData({...formData, name: e.target.value})} required />
              </div>
              <div>
                <label style={labelStyle}>Brand *</label>
                <input style={inputStyle} value={formData.brand || ''} onChange={e => setFormData({...formData, brand: e.target.value})} required />
              </div>
              <div>
                <label style={labelStyle}>Category *</label>
                <input style={inputStyle} value={formData.category || ''} onChange={e => setFormData({...formData, category: e.target.value})} required />
              </div>
              <div>
                <label style={labelStyle}>Country of Origin</label>
                <input style={inputStyle} value={formData.countryOfOrigin || ''} onChange={e => setFormData({...formData, countryOfOrigin: e.target.value})} />
              </div>
              <div>
                <label style={labelStyle}>Weight *</label>
                <input style={inputStyle} type="number" step="0.01" value={formData.weight || ''} onChange={e => setFormData({...formData, weight: parseFloat(e.target.value)})} required />
              </div>
              <div>
                <label style={labelStyle}>Weight Unit</label>
                <select style={inputStyle} value={formData.weightUnit || 'g'} onChange={e => setFormData({...formData, weightUnit: e.target.value})}>
                  <option value="g">Grams (g)</option>
                  <option value="kg">Kilograms (kg)</option>
                  <option value="oz">Ounces (oz)</option>
                  <option value="lb">Pounds (lb)</option>
                </select>
              </div>
              <div>
                <label style={labelStyle}>Shelf Life (days)</label>
                <input style={inputStyle} type="number" value={formData.shelfLifeDays || ''} onChange={e => setFormData({...formData, shelfLifeDays: parseInt(e.target.value)})} />
              </div>
              <div style={{ gridColumn: 'span 3' }}>
                <label style={labelStyle}>Description</label>
                <textarea style={{...inputStyle, height: '60px'}} value={formData.description || ''} onChange={e => setFormData({...formData, description: e.target.value})} />
              </div>
              <div style={{ gridColumn: 'span 3' }}>
                <label style={labelStyle}>Image URL</label>
                <input style={inputStyle} value={formData.imageUrl || ''} onChange={e => setFormData({...formData, imageUrl: e.target.value})} placeholder="https://..." />
              </div>
              <div style={{ gridColumn: 'span 3' }}>
                <label style={labelStyle}>Ingredients (comma-separated)</label>
                <input style={inputStyle} value={ingredientInput} onChange={e => setIngredientInput(e.target.value)} placeholder="milk, sugar, flour" />
              </div>
              <div style={{ gridColumn: 'span 3' }}>
                <label style={labelStyle}>Allergens (comma-separated)</label>
                <input style={inputStyle} value={allergenInput} onChange={e => setAllergenInput(e.target.value)} placeholder="lactose, gluten, nuts" />
              </div>
              <div style={{ gridColumn: 'span 3' }}>
                <label style={labelStyle}>Storage Instructions</label>
                <input style={inputStyle} value={formData.storageInstructions || ''} onChange={e => setFormData({...formData, storageInstructions: e.target.value})} />
              </div>
            </div>
            <div style={{ marginTop: '16px', display: 'flex', gap: '8px' }}>
              <button type="submit" style={{
                backgroundColor: '#1a237e', color: 'white', border: 'none',
                padding: '10px 20px', borderRadius: '4px', cursor: 'pointer',
              }}>
                {editingId ? 'Update' : 'Create'}
              </button>
              <button type="button" onClick={resetForm} style={{
                backgroundColor: '#757575', color: 'white', border: 'none',
                padding: '10px 20px', borderRadius: '4px', cursor: 'pointer',
              }}>
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {loading ? (
        <p>Loading...</p>
      ) : (
        <div style={{ backgroundColor: 'white', borderRadius: '8px', boxShadow: '0 1px 3px rgba(0,0,0,0.1)', overflow: 'hidden' }}>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ backgroundColor: '#f5f5f5' }}>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>UPC</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Name</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Brand</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Category</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Weight</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Status</th>
                <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #ddd' }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {products.length === 0 ? (
                <tr><td colSpan={7} style={{ padding: '24px', textAlign: 'center', color: '#999' }}>No products found</td></tr>
              ) : (
                products.map(product => (
                  <tr key={product.id} style={{ borderBottom: '1px solid #eee' }}>
                    <td style={{ padding: '12px', fontFamily: 'monospace' }}>{product.upc}</td>
                    <td style={{ padding: '12px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                        {product.imageUrl && <img src={product.imageUrl} alt="" style={{ width: '32px', height: '32px', objectFit: 'cover', borderRadius: '4px' }} />}
                        {product.name}
                      </div>
                    </td>
                    <td style={{ padding: '12px' }}>{product.brand}</td>
                    <td style={{ padding: '12px' }}>{product.category}</td>
                    <td style={{ padding: '12px' }}>{product.weight} {product.weightUnit}</td>
                    <td style={{ padding: '12px' }}>
                      <span style={{
                        padding: '2px 8px', borderRadius: '12px', fontSize: '12px',
                        backgroundColor: product.isActive ? '#e8f5e9' : '#ffebee',
                        color: product.isActive ? '#2e7d32' : '#c62828',
                      }}>
                        {product.isActive ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td style={{ padding: '12px' }}>
                      <button onClick={() => handleEdit(product)} style={{ marginRight: '8px', padding: '4px 12px', cursor: 'pointer', backgroundColor: '#e3f2fd', border: '1px solid #90caf9', borderRadius: '4px' }}>Edit</button>
                      <button onClick={() => handleDelete(product.id!)} style={{ padding: '4px 12px', cursor: 'pointer', backgroundColor: '#ffebee', border: '1px solid #ef9a9a', borderRadius: '4px', color: '#c62828' }}>Delete</button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default ProductsPage;
