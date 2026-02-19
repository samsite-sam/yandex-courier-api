export default function Home() {
  const apiEndpoints = [
    {
      method: 'POST',
      path: '/api/article-generator/full',
      description: 'Генерация полной статьи с изображением',
      params: ['referralLink (optional)', 'topic (optional)']
    },
    {
      method: 'GET',
      path: '/api/article-generator/generate',
      description: 'Проверка статуса API',
      params: []
    },
    {
      method: 'GET',
      path: '/api/article-generator/full?count=3',
      description: 'Генерация нескольких статей',
      params: ['count (1-5)', 'referralLink']
    }
  ];

  return (
    <div style={{
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
      color: '#fff',
      padding: '2rem',
      fontFamily: 'system-ui, -apple-system, sans-serif'
    }}>
      {/* Header */}
      <header style={{
        textAlign: 'center',
        marginBottom: '3rem'
      }}>
        <h1 style={{
          fontSize: '2.5rem',
          fontWeight: 700,
          marginBottom: '0.5rem',
          background: 'linear-gradient(135deg, #FFD500, #FFC300)',
          WebkitBackgroundClip: 'text',
          WebkitTextFillColor: 'transparent',
          backgroundClip: 'text'
        }}>
          🛵 Яндекс Курьер API
        </h1>
        <p style={{ color: '#94a3b8', fontSize: '1.1rem' }}>
          Автоматическая генерация SEO-статей о работе курьером
        </p>
      </header>

      {/* Features */}
      <section style={{
        maxWidth: '1000px',
        margin: '0 auto 3rem'
      }}>
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
          gap: '1.5rem'
        }}>
          {[
            { icon: '✨', title: 'AI-генерация', desc: 'Уникальные статьи с использованием AI' },
            { icon: '🔍', title: 'SEO-оптимизация', desc: 'Title, description, ключевые слова' },
            { icon: '🖼️', title: 'Изображения', desc: 'Автоматический поиск и оптимизация' },
            { icon: '🔗', title: 'Реферальные ссылки', desc: 'Интеграция CTA-кнопок' }
          ].map((feature, i) => (
            <div key={i} style={{
              background: 'rgba(255,255,255,0.05)',
              borderRadius: '12px',
              padding: '1.5rem',
              border: '1px solid rgba(255,255,255,0.1)'
            }}>
              <div style={{ fontSize: '2rem', marginBottom: '0.5rem' }}>{feature.icon}</div>
              <h3 style={{ fontSize: '1.1rem', marginBottom: '0.25rem' }}>{feature.title}</h3>
              <p style={{ color: '#94a3b8', fontSize: '0.9rem', margin: 0 }}>{feature.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* API Endpoints */}
      <section style={{
        maxWidth: '1000px',
        margin: '0 auto'
      }}>
        <h2 style={{ fontSize: '1.5rem', marginBottom: '1.5rem', textAlign: 'center' }}>
          📡 API Endpoints
        </h2>

        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          {apiEndpoints.map((endpoint, i) => (
            <div key={i} style={{
              background: 'rgba(255,255,255,0.05)',
              borderRadius: '12px',
              padding: '1.5rem',
              border: '1px solid rgba(255,255,255,0.1)'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '1rem', marginBottom: '0.5rem', flexWrap: 'wrap' }}>
                <span style={{
                  background: endpoint.method === 'POST' ? '#22c55e' : '#3b82f6',
                  color: '#fff',
                  padding: '0.25rem 0.75rem',
                  borderRadius: '4px',
                  fontSize: '0.85rem',
                  fontWeight: 600
                }}>
                  {endpoint.method}
                </span>
                <code style={{
                  background: 'rgba(0,0,0,0.3)',
                  padding: '0.25rem 0.5rem',
                  borderRadius: '4px',
                  fontSize: '0.9rem',
                  color: '#fbbf24'
                }}>
                  {endpoint.path}
                </code>
              </div>
              <p style={{ color: '#cbd5e1', margin: '0.5rem 0 0', fontSize: '0.95rem' }}>
                {endpoint.description}
              </p>
              {endpoint.params.length > 0 && (
                <div style={{ marginTop: '0.75rem' }}>
                  <span style={{ color: '#94a3b8', fontSize: '0.85rem' }}>Параметры: </span>
                  {endpoint.params.map((param, j) => (
                    <code key={j} style={{
                      background: 'rgba(0,0,0,0.3)',
                      padding: '0.125rem 0.5rem',
                      borderRadius: '4px',
                      fontSize: '0.8rem',
                      marginRight: '0.5rem',
                      color: '#a5b4fc'
                    }}>
                      {param}
                    </code>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      </section>

      {/* Example Request */}
      <section style={{
        maxWidth: '1000px',
        margin: '2rem auto'
      }}>
        <h2 style={{ fontSize: '1.5rem', marginBottom: '1rem', textAlign: 'center' }}>
          💻 Пример запроса
        </h2>
        <pre style={{
          background: 'rgba(0,0,0,0.4)',
          borderRadius: '12px',
          padding: '1.5rem',
          overflow: 'auto',
          fontSize: '0.85rem',
          border: '1px solid rgba(255,255,255,0.1)'
        }}>
{`curl -X POST ${process.env.NEXT_PUBLIC_API_URL || 'https://your-app.vercel.app'}/api/article-generator/full \\
  -H "Content-Type: application/json" \\
  -d '{
    "referralLink": "https://reg.eda.yandex.ru/?user_invite_code=YOUR_CODE"
  }'`}
        </pre>
      </section>

      {/* Footer */}
      <footer style={{
        textAlign: 'center',
        marginTop: '3rem',
        paddingTop: '2rem',
        borderTop: '1px solid rgba(255,255,255,0.1)',
        color: '#64748b'
      }}>
        <p>Создано для автоматизации контента Яндекс Еда</p>
      </footer>
    </div>
  );
}
