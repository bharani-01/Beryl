import React from 'react';

const SCENARIOS = [
  {
    time: '09:15 AM',
    text: 'Next.js website quietly deployed.',
    tag: 'Web app',
  },
  {
    time: '11:42 AM',
    text: 'Postgres snapshot saved to S3.',
    tag: 'Database',
  },
  {
    time: '01:20 PM',
    text: 'SSL certificates renewed silently.',
    tag: 'Security',
  },
  {
    time: '03:50 PM',
    text: 'Zero-downtime rolling reload.',
    tag: 'Compose',
  },
  {
    time: '06:30 PM',
    text: 'Supabase stack synced in peace.',
    tag: '1-Click stack',
  },
  {
    time: '08:45 PM',
    text: 'Traffic spike handled with ease.',
    tag: 'Ingress',
  },
  {
    time: '11:00 PM',
    text: 'Server fleet calm, resting safe.',
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
              Gentle moments in your cloud
            </h2>
            <p className="text-sm text-[#78716C] mt-1">
              Events that happen quietly without interrupting your day.
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
              <span className="text-[11px] font-medium text-stone-400 bg-stone-50 px-2 py-0.5 rounded-full border border-stone-100">
                {card.tag}
              </span>
            </div>

            {/* Bottom text: 20px stone-800, changes to pastel accent on hover */}
            <p className="text-[20px] font-medium text-[#292524] leading-snug transition-colors duration-200 group-hover:text-[#FFB7B2]">
              {card.text}
            </p>
          </div>
        ))}
      </div>
    </section>
  );
}
