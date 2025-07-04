// src/components/SelectionGrid.js
import React from 'react';

const SelectionGrid = ({ title, items, selectedItemId, onItemClick, noItemsMessage }) => {
  return (
    <div className="space-y-4">
      <p className="text-gray-600 mb-4">{title}</p>
      {items.length === 0 ? (
        <p className="text-red-500">{noItemsMessage}</p>
      ) : (
        <div className="egemer-grid">
          {items.map(item => (
            <div
              key={item.id}
              className={`group flex flex-col items-center p-2 border-2 rounded-lg transition-all duration-200 relative
              ${selectedItemId === item.id ? 'border-blue-500 bg-blue-100 shadow-lg' : 'border-gray-200 hover:border-blue-300 bg-white hover:shadow-md'}`}
              onClick={() => onItemClick(item.id)}
            >
              <div className="image-container">
                <img
                  src={item.image_url}
                  alt={item.name}
                  className="item-image transition-transform duration-300 ease-in-out group-hover:scale-150 group-hover:z-50"
                  onError={(e) => { e.target.onerror = null; e.target.src = `https://placehold.co/200x160/F0F0F0/000000?text=${encodeURIComponent(item.name.substring(0, Math.min(item.name.length, 10)))}`; }}
                />
              </div>
              <p className="text-center text-sm font-medium text-gray-700 mt-2">{item.name}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SelectionGrid;
