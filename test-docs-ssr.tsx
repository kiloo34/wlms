import React from 'react';
import { renderToString } from 'react-dom/server';
import DocsIndex from './resources/js/pages/Docs/Index';

// Minimal mock for dependencies to render on server side
// We don't have all the context, but let's try
try {
  const menu = [{ id: 1, name: 'Test', pages: [] }];
  
  // Render with page = null
  const html = renderToString(<DocsIndex page={null} menu={menu} />);
  
  if (html.includes('Belum Ada Dokumentasi')) {
    console.log('SUCCESS: Rendered fallback for null page');
  } else {
    console.log('FAILURE: Did not render fallback');
  }
} catch (e) {
  console.error('ERROR RENDER:', e);
  process.exit(1);
}

