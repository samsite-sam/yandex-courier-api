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
  "Пик часов и лучший время для работы курьера",
  "Оборудование курьера: что купить для комфортной работы",
  "Как курьеру работать в плохую погоду",
  "Правила общения с клиентами и ресторанами",
  "Как решать спорные ситуации при доставке",
  "Карьерный рост курьера в Яндекс Еда"
];

interface ArticleRequest {
  topic?: string;
  city?: string;
  referralLink?: string;
}

export async function POST(request: NextRequest) {
  try {
    const body: ArticleRequest = await request.json().catch(() => ({}));

    const zai = await ZAI.create();

    // Выбираем случайную тему или используем переданную
    const topic = body.topic || ARTICLE_TOPICS[Math.floor(Math.random() * ARTICLE_TOPICS.length)];

    // Выбираем 3-5 ключевых слов для статьи
    const selectedKeywords = KEYWORDS
      .sort(() => Math.random() - 0.5)
      .slice(0, 4);

    // Генерируем статью через AI
    const prompt = `Ты — профессиональный SEO-копирайтер для сайта о работе курьером в Яндекс Еда. Напиши полноценную, полезную статью на тему: "${topic}"

ВАЖНЫЕ ТРЕБОВАНИЯ:
1. Статья должна быть полезной, информативной и уникальной
2. Объём: минимум 1500 слов
3. Используй ключевые слова естественным образом: ${selectedKeywords.join(", ")}
4. Структура статьи с подзаголовками H2 и H3
5. Включи списки (ul/li) где уместно
6. Добавь практические советы и примеры
7. Тон: дружелюбный, мотивирующий, профессиональный

ССЫЛКА ДЛЯ РЕГИСТРАЦИИ (вставь в статью 2-3 раза):
${body.referralLink || "https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank"}

Формат ответа (строго JSON):
{
  "title": "SEO-оптимизированный заголовок (до 60 символов)",
  "metaDescription": "Описание для SEO (до 160 символов) с ключевыми словами",
  "focusKeyword": "главное ключевое слово статьи",
  "keywords": ["ключевое1", "ключевое2", "ключевое3"],
  "excerpt": "Краткое описание статьи для блога (2-3 предложения)",
  "content": "Полный HTML-контент статьи с тегами <h2>, <h3>, <p>, <ul>, <li>, <strong>. Включи 2-3 призыва к действию со ссылкой на регистрацию в виде кнопки или сильного CTA.",
  "imageSuggestion": "Описание идеального изображения для статьи (для генерации)"
}

Пиши на русском языке. Статья должна мотивировать читателя зарегистрироваться курьером.`;

    const completion = await zai.chat.completions.create({
      messages: [
        {
          role: "system",
          content: "Ты — опытный SEO-копирайтер, специализирующийся на контенте о работе в сфере доставки. Пишешь на русском языке. Всегда отвечаешь в формате JSON."
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

    // Парсим JSON из ответа
    let article;
    try {
      // Извлекаем JSON из ответа (если есть лишний текст)
      const jsonMatch = responseText.match(/\{[\s\S]*\}/);
      if (jsonMatch) {
        article = JSON.parse(jsonMatch[0]);
      } else {
        throw new Error("No JSON found in response");
      }
    } catch (parseError) {
      console.error("Failed to parse article JSON:", parseError);
      return NextResponse.json(
        { error: "Failed to parse generated article", raw: responseText },
        { status: 500 }
      );
    }

    // Добавляем метаданные
    const result = {
      ...article,
      generatedAt: new Date().toISOString(),
      topic: topic,
      wordCount: article.content?.replace(/<[^>]*>/g, "").split(/\s+/).length || 0
    };

    return NextResponse.json({
      success: true,
      article: result
    });

  } catch (error: any) {
    console.error("Article generation error:", error);
    return NextResponse.json(
      { error: error.message || "Failed to generate article" },
      { status: 500 }
    );
  }
}

// GET для проверки статуса API
export async function GET() {
  return NextResponse.json({
    status: "ok",
    service: "Yandex Courier Article Generator",
    version: "1.0.0",
    topicsCount: ARTICLE_TOPICS.length
  });
}
