import React, { useState } from 'react';
import { Plus } from 'lucide-react';

const FAQS = [
  {
    q: 'Can I really deploy any free open-source software on Beryl?',
    a: 'Yes! Beryl includes an instant 1-click catalog of over 300 free open-source software stacks—from Supabase and WordPress to Ghost, Plausible Analytics, Nextcloud, and n8n. Pick any software, click launch, and Beryl provisions the Docker containers, persistent storage volumes, Traefik reverse proxy routing, and Let\'s Encrypt SSL certificates automatically.',
  },
  {
    q: 'How does Beryl connect to my servers?',
    a: 'Beryl connects securely over SSH using standard private key authentication. There is no heavy background agent or proprietary daemon required on your host machine.',
  },
  {
    q: 'Can I host multiple open-source apps and databases on one VPS?',
    a: 'Yes. Beryl manages Traefik reverse proxy routing and Docker containers automatically, allowing you to run dozens of domains, open-source services, databases, and microservices on a single affordable server.',
  },
  {
    q: 'What happens if my server experiences a power cycle or reboot?',
    a: 'Every container orchestrated by Beryl is configured with continuous restart policies. When your server reboots, Traefik, your open-source tools, and databases resume automatically within seconds.',
  },
  {
    q: 'How does the Starter plan resource limit work?',
    a: 'The Starter plan allows up to 3 concurrently running resources (applications, databases, or open-source services) with 1 vCPU and 1GB RAM per container. You can add more resources, and simply start/stop them whenever needed.',
  },
  {
    q: 'Are automated backups included for open-source databases?',
    a: 'Yes. You can connect any S3-compatible storage provider (Cloudflare R2, AWS S3, MinIO, or Backblaze) and enable automated hourly or daily snapshot schedules with 1-click restore.',
  },
];

export default function InteractiveFaqAccordion() {
  const [openIndex, setOpenIndex] = useState(0);

  const toggle = (idx) => {
    setOpenIndex((prev) => (prev === idx ? null : idx));
  };

  return (
    <section id="faq" className="py-20 sm:py-28 bg-[#FDFCF8]">
      <div className="mx-auto max-w-3xl px-4 sm:px-6">
        
        {/* Section Header */}
        <div className="text-center mb-14">
          <span className="font-cursive text-3xl text-stone-400 block mb-1">
            clarity & peace of mind
          </span>
          <h2 className="text-3xl sm:text-4xl font-medium tracking-tight text-[#292524]">
            Frequently answered questions
          </h2>
          <p className="text-sm text-[#78716C] mt-2">
            Everything you need to know about self-hosting free open-source software calmly with Beryl.
          </p>
        </div>

        {/* Accordion container items */}
        <div className="space-y-4">
          {FAQS.map((faq, idx) => {
            const isOpen = openIndex === idx;

            return (
              <div
                key={idx}
                className="rounded-2xl border border-stone-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] overflow-hidden transition-all duration-300"
              >
                <button
                  type="button"
                  onClick={() => toggle(idx)}
                  className="w-full p-6 text-left flex items-center justify-between gap-4 font-medium text-base sm:text-lg text-[#292524] hover:text-stone-700 transition-colors cursor-pointer"
                >
                  <span>{faq.q}</span>
                  <span
                    className={`flex size-8 shrink-0 items-center justify-center rounded-full bg-stone-50 border border-stone-100 text-stone-600 transition-transform duration-300 ${
                      isOpen ? 'rotate-45 text-[#FFB7B2]' : 'rotate-0'
                    }`}
                  >
                    <Plus className="size-4" />
                  </span>
                </button>

                {isOpen && (
                  <div className="px-6 pb-6 pt-0 text-sm sm:text-[15px] text-[#78716C] leading-relaxed transition-all duration-500 ease-in-out">
                    {faq.a}
                  </div>
                )}
              </div>
            );
          })}
        </div>

      </div>
    </section>
  );
}
