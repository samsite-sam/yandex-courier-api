import { NextRequest, NextResponse } from "next/server";
import ZAI from "z-ai-web-dev-sdk";

// Ключевые слова для SEO
const KEYWORDS = [
  "работа курьером яндекс еда",
  "вакансии курьера яндекс",
  "курьер яндекс еда зарплата",
  "как стать курьером яндекс",
  "работа в яндекс еда",
  "курьер на велосипеде",
  "курьер пешком",
  "курьер на авто",
  "доставка еды яндекс",
  "подработка курьером",
  "заработок курьера яндекс",
  "оформление курьером яндекс",
  "требования к курьеру",
  "условия работы курьером",
  "график работы курьера"
];

// Темы статей для генерации
const ARTICLE_TOPICS = [
  "Как начать работать курьером Яндекс Еда: пошаговая инструкция для новичков",
  "Зарплата курьера Яндекс Еда в 2025 году: реальные цифры и факторы",
  "Советы опытных курьеров: как увеличить заработок на доставке",
  "График работы курьера Яндекс Еда: гибкость и планирование",
  "Требования к курьерам Яндекс Еда: что нужно для начала работы",
  "Работа курьером на велосипеде: преимущества и особенности",
  "Как оформить ИП для работы курьером Яндекс Еда",
  "Лучшие районы для работы курьером в вашем городе",
  "Частые ошибки начинающих курьеров и как их избежать",
  "Бонусы и акции для курьеров Яндекс Еда в 2025 году",
  "Истории успеха: курьеры, которые построили карьеру",
  "Сравнение работы курьером: Яндекс Еда vs другие сервисы",
  "Безопасность курьера: правила и рекомендации",
  "Как работать курьером студенту: совмещение с учёбой",
  "Пик часов и лучшее время для работы курьера",
  "Оборудование курьера: что купить для комфортной работы",
  "Как курьеру работать в плохую погоду",
  "Правила общения с клиентами и ресторанами",
  "Как решать спорные ситуации при доставке",
  "Карьерный рост курьера в Яндекс Еда"
];

// Слова для изображений
const IMAGE_KEYWORDS = ["delivery", "courier", "bike", "food-delivery", "messenger"];

const DEFAULT_REFERRAL_LINK = "https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank";

interface FullArticleRequest {
  topic?: string;
  referralLink?: string;
}

export async function POST(request: NextRequest) {
  try {
    const body: FullArticleRequest = await request.json().catch(() => ({}));
    const zai = await ZAI.create();

    // Выбираем случайную тему
    const topic = body.topic || ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];

    // Выбираем ключевые слова
    const selectedKeywords = KEYWORDS.sort(() => Math.random() - 0.5).slice(0, 4);
    const referralLink = body.referralLink || DEFAULT_REFERRAL_LINK;

    console.log(`[Article Generator] Generating article: ${topic}`);

    // Генерируем статью
    const prompt = `Ты — профессиональный SEO-копирайтер для сайта о работе курьером в Яндекс Еда. Напиши полноценную, полезную статью на тему: "${topic}"

ВАЖНЫЕ ТРЕБОВАНИЯ:
1. Статья должна быть полезной, информативной и уникальной
2. Объём: минимум 1500 слов
3. Используй ключевые слова естественным образом: ${selectedKeywords.join(", ")}
4. Структура статьи с подзаголовками H2 и H3
5. Включи списки (ul/li) где уместно
6. Добавь практические советы и примеры
7. Тон: дружелюбный, мотивирующий, профессиональный

ССЫЛКА ДЛЯ РЕГИСТРАЦИИ (вставь в статью 2-3 раза как кнопки и призывы к действию):
${referralLink}

Для каждого призыва к действию используй HTML вида:
<div style="text-align: center; margin: 20px 0;">
  <a href="${referralLink}" style="display: inline-block; background: linear-gradient(135deg, #FFD500, #FFC300); color: #000; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 18px;">Стать курьером Яндекс Еда</a>
</div>

Формат ответа (строго JSON):
{
  "title": "SEO-оптимизированный заголовок (до 60 символов)",
  "metaDescription": "Описание для SEO (до 160 символов) с ключевыми словами",
  "focusKeyword": "главное ключевое слово статьи",
  "keywords": ["ключевое1", "ключевое2", "ключевое3"],
  "excerpt": "Краткое описание статьи для блога (2-3 предложения)",
  "content": "Полный HTML-контент статьи с тегами <h2>, <h3>, <p>, <ul>, <li>, <strong>."
}

Пиши на русском языке. Статья должна мотивировать читателя зарегистрироваться курьером.`;

    const completion = await zai.chat.completions.create({
      messages: [
        {
          role: "system",
          content: "Ты — опытный SEO-копирайтер, специализирующийся на контенте о работе в сфере доставки. Пишешь на русском языке. Всегда отвечаешь валидным JSON."
        },
        {
          role: "user",
          content: prompt
        }
      ],
      temperature: 0.8,
      max_tokens: 4000
    });

    const responseText = completion.choices[0]?.message?.content || "";

    // Парсим JSON
    let article;
    try {
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        article = JSON.parse(jsonMatch[0]);
      } else {
        throw new Error("No JSON found");
      }
    } catch {
      return NextResponse.json({ error: "Failed to parse article", raw: responseText }, { status: 500 });
    }

    // Получаем изображение
    const imageKeyword = IMAGE_KEYWORDS[Math.floor(Math.random() * IMAGE_KEYWORDS.length)];
    const imageUrl = `https://source.unsplash.com/1200x800/?${imageKeyword}`;

    // Формируем результат
    const result = {
      title: article.title,
      metaDescription: article.metaDescription,
      focusKeyword: article.focusKeyword,
      keywords: article.keywords || selectedKeywords,
      excerpt: article.excerpt,
      content: article.content,
      image: {
        url: imageUrl,
        alt: article.title || topic,
        source: "unsplash"
      },
      metadata: {
        generatedAt: new Date().toISOString(),
        topic: topic,
        wordCount: article.content?.replace(/<[^>]*>/g, "").split(/\s+/).length || 0,
        referralLink: referralLink
      }
    };

    console.log(`[Article Generator] Article generated successfully: ${result.title} (${result.metadata.wordCount} words)`);

    return NextResponse.json({
      success: true,
      article: result
    });

  } catch (error: any) {
    console.error("[Article Generator] Error:", error);
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}

// Генерация нескольких статей сразу
export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  const count = Math.min(parseInt(searchParams.get("count") || "1"), 5);
  const referralLink = searchParams.get("referralLink") || DEFAULT_REFERRAL_LINK;

  try {
    const zai = await ZAI.create();
    const articles: any[] = [];

    for (let i = 0; i < count; i++) {
      const topic = ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];
      const selectedKeywords = KEYWORDS.sort(() => Math.random() - 0.5).slice(0, 4);

      const prompt = `Ты — SEO-копирайтер. Напиши статью: "${topic}"

Ключевые слова: ${selectedKeywords.join(", ")}
Ссылка для CTA: ${referralLink}

Требования: 1500+ слов, H2/H3 подзаголовки, списки, 2-3 CTA кнопки со ссылкой.

JSON формат:
{
  "title": "заголовок до 60 символов",
  "metaDescription": "описание до 160 символов",
  "focusKeyword": "главный ключ",
  "keywords": ["ключ1", "ключ2"],
  "excerpt": "краткое описание",
  "content": "HTML контент с <h2>, <p>, <ul>, <li>"
}`;

      const completion = await zai.chat.completions.create({
        messages: [
          { role: "system", content: "Ты SEO-копирайтер. Отвечай только JSON." },
          { role: "user", content: prompt }
        ],
        temperature: 0.8,
        max_tokens: 4000
      });

      const responseText = completion.choices[0]?.message?.content || "";
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);

      if (jsonMatch) {
        const article = JSON.parse(jsonMatch[0]);
        const imageKeyword = IMAGE_KEYWORDS[Math.floor(Math.random() * IMAGE_KEYWORDS.length)];

        articles.push({
          ...article,
          image: {
            url: `https://source.unsplash.com/1200x800/?${imageKeyword}`,
            alt: article.title
          },
          metadata: {
            generatedAt: new Date().toISOString(),
            topic: topic,
            wordCount: article.content?.replace(/<[^>]*>/g, "").split(/\s+/).length || 0
          }
        });
      }
    }

    return NextResponse.json({
      success: true,
      count: articles.length,
      articles: articles
    });

  } catch (error: any) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }
}
