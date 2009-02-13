<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <title>Silk Framework RSS Feed</title>
        <link>{$base_url}</link>
        {foreach from=$posts item=entry}
        <item>
            <title><![CDATA[{$entry->title}]]></title>
            <link>{$base_url}{$entry->url}</link>
            <guid>{$base_url}entry/{$entry->id}</guid>
            <pubDate>{$entry->post_date}</pubDate>
            <category><![CDATA[]]></category>
            <description><![CDATA[{$entry->get_summary_for_frontend()}]]></description>
        </item>
        {/foreach}
    </channel>
</rss>