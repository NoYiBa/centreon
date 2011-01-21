<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:template match="/">
<xsl:for-each select='//root'>
	<xsl:element name='table'>        
        <xsl:attribute name='width'>100%</xsl:attribute>
        <xsl:element name='tbody'>
            <xsl:element name='tr'>
                <xsl:attribute name='id'>additionalTr_<xsl:value-of select='//main/currentId'/></xsl:attribute>
                <xsl:attribute name='class'>list_one</xsl:attribute>
                <xsl:element name='td'>
				<xsl:value-of select="//main/labels/hostname"/>
				<xsl:element name='input'>
					<xsl:attribute name='type'>text</xsl:attribute>
					<xsl:attribute name='value'></xsl:attribute>
					<xsl:attribute name='name'>ldapHosts[<xsl:value-of select='//main/currentId'/>][hostname]</xsl:attribute>
				</xsl:element>
				<xsl:text>  </xsl:text>
				<xsl:value-of select="//main/labels/port"/>
				<xsl:element name='input'>
					<xsl:attribute name="size">4</xsl:attribute>
					<xsl:attribute name='type'>text</xsl:attribute>
					<xsl:attribute name='value'>389</xsl:attribute>
					<xsl:attribute name='name'>ldapHosts[<xsl:value-of select='//main/currentId'/>][port]</xsl:attribute>
				</xsl:element>
				<xsl:text>  </xsl:text>
				<xsl:value-of select="//main/labels/ssl"/>
				<xsl:element name="input">
					<xsl:attribute name='type'>checkbox</xsl:attribute>
					<xsl:attribute name='name'>ldapHosts[<xsl:value-of select='//main/currentId'/>][use_ssl]</xsl:attribute>
					<xsl:attribute name='value'>1</xsl:attribute>
				</xsl:element>
				<xsl:text>  </xsl:text>
				<xsl:value-of select="//main/labels/tls"/>
				<xsl:element name="input">
					<xsl:attribute name='type'>checkbox</xsl:attribute>
					<xsl:attribute name='name'>ldapHosts[<xsl:value-of select='//main/currentId'/>][use_tls]</xsl:attribute>
					<xsl:attribute name='value'>1</xsl:attribute>
				</xsl:element>
				<xsl:text>   </xsl:text>
                               <xsl:value-of select='//main/labels/order'/>
                               <xsl:element name='input'>
                                   <xsl:attribute name='name'>ldapHosts[<xsl:value-of select='//main/currentId'/>][order]</xsl:attribute>
                                   <xsl:attribute name='type'>text</xsl:attribute>
                                   <xsl:attribute name='size'>2</xsl:attribute>
                                   <xsl:attribute name='value'><xsl:value-of select='//main/orderValue'/></xsl:attribute>
                               </xsl:element>
				<xsl:text>  </xsl:text>
                               <xsl:element name='img'>
                                   <xsl:attribute name='src'>./img/icones/16x16/delete.gif</xsl:attribute>
                                   <xsl:attribute name='style'>cursor: pointer;</xsl:attribute>
                                   <xsl:attribute name='onClick'>
                                       if (confirm("<xsl:value-of select='//main/labels/confirmDeletion'/>")) {
                                           removeTr("additionalTr_<xsl:value-of select='//main/currentId' />");
                                       }
                                   </xsl:attribute>
                 	</xsl:element>
                </xsl:element>
            </xsl:element>
        </xsl:element>
    </xsl:element>
    <xsl:element name='div'>
        <xsl:attribute name='id'><xsl:value-of select='//main/nextRowId'/></xsl:attribute>        
    </xsl:element>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>