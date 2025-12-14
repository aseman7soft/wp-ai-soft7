# Aseman Robot - User Guide

## Quick Start Guide

### Installation

1. **Upload Plugin**
   - Download the plugin files
   - Upload to `/wp-content/plugins/aseman-robot/`
   - OR upload via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate Plugin**
   - Go to Plugins page
   - Find "ربات آسمان (Aseman Robot)"
   - Click "Activate"

3. **Configure AI Provider**
   - Navigate to **ربات آسمان > Settings**
   - Choose your AI provider (Remote or Local)
   - Enter credentials and settings
   - Click "Test Connection" to verify
   - Save Settings

### Using Remote AI (OpenAI)

1. Go to **ربات آسمان > Settings**
2. Select Provider Mode: **Remote API (OpenAI-compatible)**
3. Fill in:
   - **API Base URL**: `https://api.openai.com/v1`
   - **API Token**: Your OpenAI API key (starts with `sk-`)
   - **Model Name**: `gpt-4` or `gpt-3.5-turbo`
4. Set **Temperature**: 0.7 (higher = more creative)
5. Set **Max Tokens**: 4000 (adjust based on model limits)
6. Click **Test Connection** - should show success
7. Click **Save Settings**

### Using Local AI (LM Studio)

1. **Start LM Studio**
   - Download and install LM Studio
   - Load your preferred model
   - Start the local server (usually on port 1234)

2. **Configure Plugin**
   - Go to **ربات آسمان > Settings**
   - Select Provider Mode: **Local AI (LM Studio / Ollama)**
   - Local Base URL: `http://localhost:1234/v1`
   - Endpoint Type: **OpenAI-Compatible Chat Completions**
   - Model Name: Your loaded model name
   - Token: Leave empty (usually not required)
   - Click **Test Connection**
   - Click **Save Settings**

### Using Local AI (Ollama)

1. **Start Ollama**
   ```bash
   ollama serve
   ollama pull llama2  # or your preferred model
   ```

2. **Configure Plugin**
   - Go to **ربات آسمان > Settings**
   - Select Provider Mode: **Local AI (LM Studio / Ollama)**
   - Local Base URL: `http://localhost:11434`
   - Endpoint Type: **Ollama Native** OR **OpenAI-Compatible**
   - Model Name: `llama2` (or your model)
   - Click **Test Connection**
   - Click **Save Settings**

## Generating Content

### Basic Content Generation

1. Go to **ربات آسمان > Generate Content**
2. Fill in the form:
   - **Topic** (required): Main subject for articles
   - **Keywords** (optional): 3 related keywords
   - **Category**: Choose WordPress category
   - **Publish Mode**: Draft Now / Publish Now / Schedule
3. Click **Add to Queue**
4. Job will be processed automatically within 5 minutes

### Example: Generate Blog Posts

**Topic**: "Benefits of Remote Work"

**Keywords**:
- Work from home
- Remote productivity
- Digital nomad

**Category**: Business

**Publish Mode**: Draft Now

**Result**: 3 unique articles will be created as drafts, ready for review before publishing.

### Scheduling Articles

1. Fill in the generation form
2. Select **Publish Mode**: Schedule
3. Set **Schedule Start**: Date and time for first article
4. Set **Interval**: Minutes between articles (e.g., 10 minutes)
5. Submit

**Example**: 
- Start: 2024-01-15 10:00 AM
- Interval: 30 minutes
- Result: Article 1 at 10:00 AM, Article 2 at 10:30 AM, Article 3 at 11:00 AM

## Managing the Queue

### Queue Manager Page

Go to **ربات آسمان > Queue Manager** to:

- View all generation jobs
- Filter by status (Pending, Processing, Done, Failed)
- Filter by language
- See progress (0/3, 1/3, 2/3, 3/3)
- View generated post links
- Retry failed jobs
- Cancel pending jobs
- Delete jobs
- Clone jobs with Force Generate

### Job Statuses

- **Pending**: Waiting to be processed
- **Processing**: Currently generating content
- **Done**: Successfully completed
- **Failed**: Failed after max attempts
- **Scheduled**: Scheduled for future publication

### Retry a Failed Job

1. Go to Queue Manager
2. Find the failed job
3. Click **Retry** button
4. Job will be reset and reprocessed

### Force Generate (Bypass Duplicate Lock)

If you want to generate content for a topic that already exists:

1. Go to Queue Manager
2. Find the existing job
3. Click **Clone Force** button
4. New job created with duplicate lock bypassed

OR:

1. Go to Generate Content page
2. Fill in the form
3. Check **Force Generate (Ignore Duplicate Lock)**
4. Submit

## Understanding Duplicate Lock

### What is Duplicate Lock?

The plugin prevents generating the same content multiple times by checking if a similar job already exists within a time window.

### Lock Scope Options

**Topic Only**: Only checks topic
- "WordPress SEO" would block another "WordPress SEO"

**Topic + Language**: Checks topic and language
- "WordPress SEO" in English blocks same topic in English
- But allows "WordPress SEO" in Persian

**Topic + Language + Category**: Checks topic, language, and category
- Same topic in same language and category is blocked
- But allows same topic in different category

**Topic + Keywords + Language + Category** (Strict - Default):
- Checks everything including keywords
- Most strict option
- Recommended for preventing exact duplicates

### When to Use Force Generate?

- Updating old content with fresh generation
- Testing different AI parameters
- Creating intentional variations
- Seasonal updates (e.g., "2024 Guide" vs "2025 Guide")

### Configuring Duplicate Lock

1. Go to **ربات آسمان > Settings**
2. Click **Duplicate Lock** tab
3. Set **Lock Window** (hours): How long to check for duplicates
4. Set **Lock Scope**: How strictly to check
5. Enable/disable **Allow Force Generate**
6. Enable/disable **Strict Lock (Include Failed Jobs)**
7. Save Settings

## Viewing Reports

### Reports Dashboard

Go to **ربات آسمان > Reports Dashboard** to view:

- Total jobs created
- Completed vs Failed jobs
- Posts generated count
- Published vs Draft vs Scheduled posts
- Top error messages
- Recent activity

### Time Period Filter

- **Today**: Jobs created today
- **Last 7 Days**: Jobs in the past week
- **Last 30 Days**: Jobs in the past month

### Understanding Statistics

**Total Jobs**: All generation jobs submitted
**Completed Jobs**: Successfully generated 3 articles
**Pending Jobs**: Waiting in queue
**Failed Jobs**: Failed after max retry attempts
**Posts Generated**: Total WordPress posts created
**Published**: Posts with published status
**Drafts**: Posts with draft status
**Scheduled**: Posts scheduled for future

## Settings Configuration

### Prompt Template

The prompt template controls how the AI generates content.

**Default Template**: SEO-optimized, formal articles with:
- Unique titles
- 5-8 H2 sections
- H3 subsections
- Conclusion
- FAQ section
- Meta description
- Tags

**Placeholders Available**:
- `{topic}`: Main topic
- `{keyword1}`, `{keyword2}`, `{keyword3}`: Keywords
- `{category}`: WordPress category
- `{language}`: Site language
- `{tone}`: Article tone (Formal)
- `{min_words}`: Minimum word count

**Customizing Template**:
1. Go to Settings > Prompt Template tab
2. Edit the template text
3. Use placeholders as needed
4. Save Settings

### Default Settings

**Default Post Status**:
- Draft: Posts created as drafts
- Publish: Posts published immediately

**Schedule Spacing**: Default minutes between scheduled articles

**Minimum Words**: Target word count per article

**Jobs Per Cron Run**: How many jobs to process per 5-minute cycle

## Troubleshooting

### "Job added to queue but nothing happens"

**Check WP-Cron**:
- WP-Cron must be enabled (it usually is by default)
- If using external cron, ensure it's configured correctly
- Wait at least 5 minutes for processing to start

### "Connection Test Failed"

**Remote API**:
- Verify API token is correct
- Check API base URL format
- Ensure no trailing slash in URL
- Check firewall/network connectivity
- Verify API quota/limits

**Local AI**:
- Ensure LM Studio or Ollama is running
- Check port numbers (1234 for LM Studio, 11434 for Ollama)
- Verify model is loaded
- Test with curl:
  ```bash
  curl http://localhost:1234/v1/models
  ```

### "Job Failed: Invalid JSON response"

The AI didn't return valid JSON format:
- Try increasing Temperature (more structured)
- Try different model
- Check prompt template format
- Review AI output in logs

### "A similar job already exists"

Duplicate lock prevented generation:
- Check if you really want a duplicate
- Use **Force Generate** to bypass
- Adjust lock window in Settings
- Change lock scope to be less strict

### "Failed to create post"

WordPress post creation failed:
- Check WordPress user permissions
- Verify category exists
- Check database connection
- Review error message in Queue Manager

## Advanced Usage

### Multi-Language Sites

The plugin automatically detects your WordPress site language:
- Persian sites (fa_IR): Persian UI with RTL layout
- Arabic sites (ar): Arabic UI with RTL layout
- Other languages: English UI with LTR layout

Generated content language can be controlled via the prompt template.

### SEO Integration

The plugin automatically integrates with:
- **Yoast SEO**: Sets meta description
- **Rank Math**: Sets meta description
- **Default WordPress**: Saves in post meta

### Scheduling Strategy

**Daily Posts**:
- Generate 1 job per day
- Set interval to 480 minutes (8 hours)
- Articles spread across the day

**Weekly Series**:
- Generate 1 job per week
- Set interval to 1440 minutes (24 hours)
- Articles published on consecutive days

**Burst Publishing**:
- Generate multiple jobs
- Set interval to 5 minutes
- Quick content publication

### Monitoring Performance

1. Go to Reports Dashboard regularly
2. Check failed jobs and error messages
3. Review generated posts for quality
4. Adjust prompt template as needed
5. Fine-tune AI parameters (temperature, max tokens)

## Best Practices

### Content Quality

1. **Review Generated Content**: Always review drafts before publishing
2. **Edit and Enhance**: Add personal touch, images, links
3. **Fact-Check**: Verify AI-generated facts
4. **SEO Optimization**: Review meta descriptions and tags

### Topic Selection

1. **Be Specific**: "WordPress Security Best Practices" better than "WordPress"
2. **Use Keywords**: Include 2-3 relevant keywords
3. **Target Category**: Choose appropriate category
4. **Avoid Duplicates**: Check existing content first

### AI Parameter Tuning

- **Temperature 0.5-0.7**: Balanced, consistent content
- **Temperature 0.8-1.0**: More creative, varied content
- **Max Tokens 3000-4000**: Good for long-form articles
- **Max Tokens 2000-3000**: Shorter, focused content

### Queue Management

1. **Monitor Regularly**: Check queue daily
2. **Retry Failed Jobs**: Review error and retry
3. **Clean Old Jobs**: Delete completed jobs periodically
4. **Limit Queue Size**: Don't queue too many at once

## Support and Resources

- **GitHub Issues**: Report bugs and request features
- **Documentation**: Review README.md and TECHNICAL.md
- **WordPress Codex**: For WordPress-specific questions
- **AI Provider Docs**: For API-specific issues

## Frequently Asked Questions

**Q: How many articles are generated per job?**  
A: Exactly 3 unique articles per job (fixed).

**Q: Can I change the number of articles?**  
A: No, the plugin is designed for 3 articles per topic for optimal variation.

**Q: How long does generation take?**  
A: Depends on AI provider and model. Usually 1-3 minutes per job.

**Q: Are generated articles unique?**  
A: Yes, the AI generates unique content for each article, even within the same job.

**Q: Can I use multiple AI providers?**  
A: You can configure one provider at a time. Switch in Settings as needed.

**Q: Is local AI as good as OpenAI?**  
A: Depends on the model. Large models (13B+) can produce quality results, but may be slower.

**Q: How much does this cost?**  
A: The plugin is free. Costs depend on your AI provider (OpenAI API charges per token, local AI is free but requires hardware).

**Q: Will this work on shared hosting?**  
A: Yes, if WP-Cron is enabled. Local AI requires your own server/computer.

**Q: Can I schedule articles months in advance?**  
A: Yes, but WP-Cron must remain active. For far-future scheduling, verify cron is working.

**Q: What if I run out of API quota?**  
A: Jobs will fail with API error. They'll retry automatically. Add API quota or switch to local AI.

## Getting Help

If you encounter issues:

1. Check this User Guide
2. Review TECHNICAL.md for advanced topics
3. Check plugin logs (wp_aseman_logs table)
4. Test AI connection in Settings
5. Review WordPress debug.log
6. Open GitHub issue with error details

---

**Happy Content Creating with Aseman Robot! 🚀**
