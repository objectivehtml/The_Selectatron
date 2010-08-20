# The Selectatron

### Overview

PLEASE NOTE this is a work in progress, not ready for prime time but free to play in a dev environment and send some feedback.

This fieldtype for EE2 is a one to many relationship tool, on a shoestring. It lets you select entries from multiple channels, and store their entry ids in a 12|22|44|45 format. It also lets you store the selected entries as native EE relationships, so that you can use the reverse_related_entries tag to work entries from the other direction.

The output from the field is primarily for use in embeds to templates with `exp:channel:entries` tags, and using the `fixed_order` parameter.

### Example Usage

Say you had a channel for storing content for sidebar modules around your site. You want your publisher to be able to select from a list of available modules (which are EE entries), and also allow the publisher to choose the order they are displayed in.

![Screebshot](http://iain.co.nz/core/gfx/selectatron.png)

With this fieldtype, you can do just that. Each selection from the user is stored in a 12|22|44|45 format, so you can simply pass this to your embedded template, which has a regular exp:channel:entries tag and use the fixed_order parameter to control the order.

For example, in your parent template

	{exp:channel:entries channel="news"}
		<h3>{title}</h3>
		{main_article}
		{embed=".includes/sidebar_modules" entries="{my_selectatron_field}"}
	{/exp:channel:entries}

Then, within your `.includes/sidebar_modules` template, you'd output the entries using the following code:

	{exp:channel:entries channel="sidebar_modules" fixed_order="{embed:entries}"}
		<div class="module">
			{title}
			{whatever_fields_you_want}
		</div>
	{/exp:channel:entries}

The fieldtype has a drag drop interface for ordering selections, and entry ids are stored in your selection order. Storing the relationship data is optional, and for the time being is only available using the reverse_related_entries tag. The method above is essentially the same as the {related_entries tag} which won't work due to the way the data is being stored.

I plan to look at this further to see what hooks I can use to allow the related_entries tag or similar to work.

### Installation

Download the package & rename the download to 'the_selectatron'. 
Drop it in your /system/expressionengine/third_party/ folder.
Select the channels you'd like to 'relate' and also if you want the field to store native relationship data.

Activate the fieldtype.

If you want to install and run with EE installed above the root, you'll need to move the `selectatron_assets` folder, there's a var you can update on line 23 of the fieldtype to define the location. I plan to move this to third_party themes folder at some point.

### Restrictions

Unless you have been granted prior, written consent from Iain Urquhart, you may not:

 * Reproduce, distribute, or transfer the Software, or portions thereof, to any third party.
 * Sell, rent, lease, assign, or sublet the Software or portions thereof.
 * Grant rights to any other person.
 * Use the Software in violation of any international law or regulation.

*** Disclaimer Of Warranty

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, WARRANTIES OF QUALITY, PERFORMANCE, NON-INFRINGEMENT, MERCHANTABILITY, OR FITNESS FOR A PARTICULAR PURPOSE. FURTHER, IAIN URQUHART DOES NOT WARRANT THAT THE SOFTWARE OR ANY RELATED SERVICE WILL ALWAYS BE AVAILABLE.

*** Limitations Of Liability

YOU ASSUME ALL RISK ASSOCIATED WITH THE INSTALLATION AND USE OF THE SOFTWARE. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS OF THE SOFTWARE BE LIABLE FOR CLAIMS, DAMAGES OR OTHER LIABILITY ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE. LICENSE HOLDERS ARE SOLELY RESPONSIBLE FOR DETERMINING THE APPROPRIATENESS OF USE AND ASSUME ALL RISKS ASSOCIATED WITH ITS USE, INCLUDING BUT NOT LIMITED TO THE RISKS OF PROGRAM ERRORS, DAMAGE TO EQUIPMENT, LOSS OF DATA OR SOFTWARE PROGRAMS, OR UNAVAILABILITY OR INTERRUPTION OF OPERATIONS.