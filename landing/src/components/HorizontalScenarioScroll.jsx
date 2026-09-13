import React from 'react';

const SCENARIOS = [
  {
    time: '09:15 AM',
    text: 'Supabase backend live in 60 seconds.',
    tag: '1-Click Open Source',
  },
  {
    time: '10:30 AM',
    text: 'Ghost publishing studio launched.',
    tag: 'Free CMS',
  },
  {
    time: '11:42 AM',
    text: 'PostgreSQL 17 snapshot saved to S3.',
    tag: 'Database',
  },
  {
    time: '01:20 PM',
    text: 'Plausible privacy metrics active.',
    tag: 'Free Analytics',
  },
  {
    time: '03:50 PM',
    text: 'n8n workflow automation online.',
    tag: 'Free Automation',
  },
  {
    time: '06:30 PM',
    text: 'MinIO S3 storage synced in peace.',
    tag: 'Object Storage',
  },
  {
    time: '08:45 PM',
    text: 'WordPress instance launched calmly.',
    tag: '1-Click App',
  },
  {
    time: '11:00 PM',
    text: 'Server fleet calm, CPU resting at 6%.',
    tag: 'Hardware',
  },
];

export default function HorizontalScenarioScroll() {
  return (
    <section id="scenarios" className="py-12 sm:py-16 overflow-hidden">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mb-6">
        <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
          <div>
            <h2 className="text-2xl sm:text-3xl font-medium tracking-tight text-[#292524]">
              Gentle moments in your open-source cloud
            </h2>
            <p className="text-sm text-[#78716C] mt-1">
              Deploy free open-source software and services that run quietly without interrupting your day.
            </p>
          </div>
          <span className="text-xs font-medium text-stone-400">
            Scroll horizontally →
          </span>
        </div>
      </div>

      {/* Horizontal scroll track */}
      <div className="flex gap-4 overflow-x-auto no-scrollbar px-4 sm:px-8 lg:px-12 pb-4 pt-1">
        {SCENARIOS.map((card, idx) => (
          <div
            key={idx}
            className="group flex-shrink-0 w-[288px] h-[160px] rounded-3xl bg-white p-5 border border-stone-100 flex flex-col justify-between shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_25px_-2px_rgba(0,0,0,0.08)] hover:-translate-y-1 transition-all duration-300 cursor-default"
          >
            {/* Top row: timestamp */}
            <div className="flex items-center justify-between">
              <span className="text-sm font-normal text-stone-400">
                {card.time}
              </span>
              <span className="text-[11px] font-medium text-stone-500 bg-stone-50 px-2 py-0.5 rounded-full border border-stone-100">
                {card.tag}
              </span>
            </div>

            {/* Bottom text: 20px stone-800, changes to pastel accent on hover */}
            <p className="text-[19px] sm:text-[20px] font-medium text-[#292524] leading-snug transition-colors duration-200 group-hover:text-[#FFB7B2]">
              {card.text}
            </p>
          </div>
        ))}
      </div>
    </section>
  );
}
