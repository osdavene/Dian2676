const express = require('express');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Rutas de API
app.use('/api/jobs', require('./routes/jobs'));
app.use('/api/questions', require('./routes/questions'));
app.use('/api/sessions', require('./routes/sessions'));
app.use('/api/stats', require('./routes/stats'));

// Servir frontend compilado en producción si existe
const distPath = path.join(__dirname, '../client/dist');
app.use(express.static(distPath));

// Healthcheck
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok', service: 'DIAN 2676 API Simulator', timestamp: new Date() });
});

// Fallback SPA
app.get('*', (req, res, next) => {
  if (req.path.startsWith('/api')) return next();
  res.sendFile(path.join(distPath, 'index.html'), (err) => {
    if (err) {
      res.status(200).send('API de Concurso DIAN 2676 activa en el puerto ' + PORT + '. Inicie el cliente de desarrollo o compile la aplicación.');
    }
  });
});

app.listen(PORT, () => {
  console.log(`====================================================`);
  console.log(` Servidor DIAN 2676 activo en: http://localhost:${PORT}`);
  console.log(` PostgreSQL conectado en: ${process.env.DB_HOST}:${process.env.DB_PORT}/${process.env.DB_NAME}`);
  console.log(`====================================================`);
});
